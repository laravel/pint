<?php

namespace App\Actions;

use App\Contracts\HasPrettierDependencies;
use App\Enums\NodePackageManager;
use App\Factories\ConfigurationFactory;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Prettier;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;

class EnsurePrettierIsConfigured
{
    /**
     * Create a new ensure prettier is configured action instance.
     */
    public function __construct(
        protected Prettier $prettier,
        protected ConfigurationJsonRepository $configuration,
    ) {
        //
    }

    /**
     * Ensure prettier is configured.
     */
    public function execute(): void
    {
        if (! $this->needsPrettier()) {
            return;
        }

        $this->ensureNodeIsInstalled()
            ->ensureNodeDependenciesAreInstalled()
            ->ensurePrettierNodeDependencyIsConfigured();
    }

    /**
     * Determine whether prettier should be configured for the current execution.
     */
    protected function needsPrettier(): bool
    {
        $rules = $this->configuration->rules();

        return collect(ConfigurationFactory::customFixers())
            ->filter(fn ($fixer) => $fixer instanceof HasPrettierDependencies)
            ->contains(fn ($fixer) => ($rules[$fixer->getName()] ?? false) === true);
    }

    /**
     * The prettier dependencies required by every enabled rule.
     *
     * @return array<int, string>
     */
    public function requiredPackages(): array
    {
        return collect(ConfigurationFactory::customFixers())
            ->filter(fn ($fixer) => $fixer instanceof HasPrettierDependencies)
            ->flatMap(fn (HasPrettierDependencies $fixer) => $fixer->prettierDependencies())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ensure node is installed.
     */
    protected function ensureNodeIsInstalled(): static
    {
        if (Process::run('node -v')->failed()) {
            abort(1, 'The rules enabled in your pint configuration require Node.js to be installed.');
        }

        return $this;
    }

    /**
     * Ensure the required prettier packages are installed in the project.
     */
    protected function ensureNodeDependenciesAreInstalled(): static
    {
        $missing = collect($this->requiredPackages())
            ->reject(fn (string $package): bool => $this->isResolvable($package))
            ->values()
            ->all();

        if ($missing === []) {
            return $this;
        }

        $projectRoot = $this->prettier->projectRoot();

        $manager = NodePackageManager::detect($projectRoot);

        $confirmed = confirm(
            label: sprintf(
                'The rules enabled in your pint configuration require the following prettier dependencies to be installed using [%s]: %s. Would you like to install them now?',
                $manager->binary(),
                implode(', ', $missing),
            ),
            default: false,
        );

        if (! $confirmed) {
            abort(1, sprintf(
                'The rules enabled in your pint configuration require the following prettier dependencies to be installed: %s.',
                implode(', ', $missing),
            ));
        }

        $this->ensurePackageJsonExists();

        progress(
            label: sprintf('Installing prettier dependencies using [%s]', $manager->binary()),
            steps: $missing,
            callback: function (string $package, $progress) use ($manager, $projectRoot): void {
                $progress->hint(sprintf('Installing [%s]...', $package));

                $result = Process::path($projectRoot)->run($manager->installCommand([$package]));

                if ($result->failed()) {
                    abort(1, sprintf(
                        'The rules enabled in your pint configuration were unable to install their prettier dependencies using [%s]. Reason: %s',
                        $manager->binary(),
                        $result->errorOutput() ?: $result->output(),
                    ));
                }
            },
        );

        return $this;
    }

    /**
     * Ensure the project's own prettier configuration matches the options Pint expects.
     */
    protected function ensurePrettierNodeDependencyIsConfigured(): static
    {
        if (! $this->prettier->hasCustomPrettierConfig()) {
            return $this;
        }

        $missingPlugins = collect($this->requiredPackages())
            ->reject(fn (string $plugin): bool => $this->prettier->hasPlugins([$plugin]))
            ->values()
            ->all();

        $mismatchedOptions = $this->mismatchedOptions(
            $this->prettier->defaultOptions(),
            $this->prettier->resolveCustomOptions(),
        );

        if ($missingPlugins === [] && $mismatchedOptions === []) {
            return $this;
        }

        $messages = [];

        if ($missingPlugins !== []) {
            $messages[] = sprintf(
                'add the following prettier plugins: %s',
                implode(', ', $missingPlugins),
            );
        }

        if ($mismatchedOptions !== []) {
            $messages[] = sprintf(
                'set the following prettier options: %s',
                implode(', ', array_map(
                    fn (string $option, mixed $value): string => sprintf(
                        '%s = %s',
                        $option,
                        json_encode($value, JSON_UNESCAPED_SLASHES),
                    ),
                    array_keys($mismatchedOptions),
                    array_values($mismatchedOptions),
                )),
            );
        }

        abort(1, sprintf(
            'The rules enabled in your pint configuration require your prettier configuration to %s.',
            implode('; and ', $messages),
        ));
    }

    /**
     * The expected options that are missing from, or differ in, the project's configuration.
     *
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $actual
     * @return array<string, mixed>
     */
    protected function mismatchedOptions(array $expected, array $actual): array
    {
        unset($expected['plugins']);

        return collect($expected)
            ->reject(fn (mixed $value, string $option): bool => array_key_exists($option, $actual) && $actual[$option] === $value)
            ->all();
    }

    /**
     * Determine whether the given package is resolvable from the project root.
     */
    protected function isResolvable(string $package): bool
    {
        return Process::path($this->prettier->projectRoot())
            ->run(['node', '-e', "require.resolve('{$package}', { paths: [process.cwd()] })"])
            ->successful();
    }

    /**
     * Ensure a "package.json" exists so dependencies can be saved.
     */
    protected function ensurePackageJsonExists(): void
    {
        $packageJson = $this->prettier->projectRoot().'/package.json';

        if (! File::exists($packageJson)) {
            File::put($packageJson, json_encode([
                '$schema' => 'https://www.schemastore.org/package.json',
                'private' => true,
                'type' => 'module',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        }
    }
}
