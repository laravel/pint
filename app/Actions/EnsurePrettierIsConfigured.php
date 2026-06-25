<?php

namespace App\Actions;

use App\Contracts\HasPrettierDependencies;
use App\Enums\NodePackageManager;
use App\Factories\ConfigurationFactory;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Prettier;
use Composer\Semver\Semver;
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
            ->ensureNodeDependenciesAreInstalled();
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
     * The prettier dependencies required by every enabled rule, mapped to the
     * semver constraint each package must satisfy.
     *
     * @return array<string, string>
     */
    public function requiredPackages(): array
    {
        return collect(ConfigurationFactory::customFixers())
            ->filter(fn ($fixer) => $fixer instanceof HasPrettierDependencies)
            ->flatMap(fn (HasPrettierDependencies $fixer) => $fixer->prettierDependencies())
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
     * Ensure the required prettier packages are installed and satisfy their
     * required versions.
     */
    protected function ensureNodeDependenciesAreInstalled(): static
    {
        $required = $this->requiredPackages();

        $projectRoot = $this->prettier->projectRoot();

        $manager = NodePackageManager::detect($projectRoot);

        $probes = collect($required)
            ->map(fn (string $constraint, string $package): array => $this->probe($package))
            ->all();

        $missing = collect($probes)
            ->reject(fn (array $probe): bool => $probe['resolved'])
            ->keys()
            ->all();

        if ($missing !== []) {
            $this->installMissing($missing, $required, $manager, $projectRoot);

            // Re-probe the freshly installed packages so their versions are validated below.
            foreach ($missing as $package) {
                $probes[$package] = $this->probe($package);
            }
        }

        $outdated = $this->unsatisfied($probes, $required);

        if ($outdated !== []) {
            abort(1, sprintf(
                "The following prettier dependencies do not satisfy the versions required by your pint configuration:\n%s\n\nUpdate them using [%s]: %s",
                collect($outdated)
                    ->map(fn (array $dependency): string => sprintf(
                        '  - %s (installed: %s, required: %s)',
                        $dependency['package'],
                        $dependency['installed'],
                        $dependency['constraint'],
                    ))
                    ->implode("\n"),
                $manager->binary(),
                implode(' ', $manager->installCommand(collect($outdated)->map(
                    fn (array $dependency): string => $this->spec($dependency['package'], $dependency['constraint']),
                )->all())),
            ));
        }

        return $this;
    }

    /**
     * Prompt for and install the given missing packages, pinned to their required versions.
     *
     * @param  array<int, string>  $missing
     * @param  array<string, string>  $required
     */
    protected function installMissing(array $missing, array $required, NodePackageManager $manager, string $projectRoot): void
    {
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
            callback: function (string $package, $progress) use ($manager, $projectRoot, $required): void {
                $progress->hint(sprintf('Installing [%s]...', $package));

                $result = Process::path($projectRoot)->run(
                    $manager->installCommand([$this->spec($package, $required[$package])]),
                );

                if ($result->failed()) {
                    abort(1, sprintf(
                        'The rules enabled in your pint configuration were unable to install their prettier dependencies using [%s]. Reason: %s',
                        $manager->binary(),
                        $result->errorOutput() ?: $result->output(),
                    ));
                }
            },
        );
    }

    /**
     * Determine which resolved packages do not satisfy their required version.
     *
     * @param  array<string, array{resolved: bool, version: string|null}>  $probes
     * @param  array<string, string>  $required
     * @return array<int, array{package: string, installed: string, constraint: string}>
     */
    protected function unsatisfied(array $probes, array $required): array
    {
        return collect($probes)
            ->filter(fn (array $probe): bool => $probe['resolved'] && $probe['version'] !== null)
            ->reject(fn (array $probe, string $package): bool => Semver::satisfies($probe['version'], $required[$package]))
            ->map(fn (array $probe, string $package): array => [
                'package' => $package,
                'installed' => $probe['version'],
                'constraint' => $required[$package],
            ])
            ->values()
            ->all();
    }

    /**
     * Probe the given package, reporting whether it resolves and its installed version.
     *
     * @return array{resolved: bool, version: string|null}
     */
    protected function probe(string $package): array
    {
        $result = Process::path($this->prettier->projectRoot())
            ->run(['node', $this->prettier->versionProbePath(), $package]);

        if ($result->failed()) {
            return ['resolved' => false, 'version' => null];
        }

        $version = trim($result->output());

        return ['resolved' => true, 'version' => $version === '' ? null : $version];
    }

    /**
     * Build the install specifier pinning a package to its required constraint.
     */
    protected function spec(string $package, string $constraint): string
    {
        return $package.'@'.$constraint;
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
