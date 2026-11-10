<?php

namespace App\Repositories;

use App\Project;
use PhpCsFixer\Cache\Cache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Path;

class ConfigurationJsonRepository
{
    /**
     * Lists the finder options.
     *
     * @var array<int, string>
     */
    protected $finderOptions = [
        'exclude',
        'in',
        'notPath',
        'notName',
    ];

    /**
     * Create a new Configuration Json Repository instance.
     *
     * @param  string|null  $path
     * @param  string|null  $preset
     * @return void
     */
    public function __construct(protected $path, protected $preset)
    {
        //
    }

    /**
     * Get the finder options.
     *
     * @return array<string, array<int, string>|string>
     */
    public function finder()
    {
        return collect($this->get())
            ->filter(fn ($value, $key) => in_array($key, $this->finderOptions))
            ->toArray();
    }

    /**
     * Get the rules options.
     *
     * The "--blade" option is a shortcut that toggles the [Pint/laravel_blade]
     * rule, so it is folded into the configured rules here.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = $this->get()['rules'] ?? [];

        if (app()->bound(InputInterface::class)) {
            $input = resolve(InputInterface::class);

            if ($input->hasOption('blade') && $input->getOption('blade') === true) {
                $rules['Pint/laravel_blade'] = true;
            }
        }

        return $rules;
    }

    /**
     * Get the cache file location.
     *
     * @return string|null
     */
    public function cacheFile()
    {
        $cacheFile = $this->get()['cache-file'] ?? null;

        if (is_null($cacheFile) || ! $this->isProjectConfiguration()) {
            return $cacheFile;
        }

        $cacheFile = $this->resolveProjectPath('cache-file', $cacheFile);

        if (is_file($cacheFile) && filesize($cacheFile) > 0 && ! $this->isCache($cacheFile)) {
            abort(1, sprintf('The cache file [%s] already exists and is not a Pint cache.', $cacheFile));
        }

        return $cacheFile;
    }

    /**
     * Determine if the configuration defines paths to inspect.
     *
     * @return bool
     */
    public function hasIncludedPaths()
    {
        return ! empty($this->finder()['in'] ?? null);
    }

    /**
     * Get the preset option.
     *
     * @return string
     */
    public function preset()
    {
        return $this->preset ?: ($this->get()['preset'] ?? 'laravel');
    }

    /**
     * Get the configuration from the "pint.json" file.
     *
     * @return array<string, array<int, string>|string>
     */
    protected function get()
    {
        if (! is_null($this->path) && $this->fileExists((string) $this->path)) {
            $baseConfig = json_decode(file_get_contents($this->path), true);

            if (isset($baseConfig['extend'])) {
                $baseConfig = $this->resolveExtend($baseConfig);
            }

            return tap($baseConfig, function ($configuration) {
                if (! is_array($configuration)) {
                    abort(1, sprintf('The configuration file [%s] is not valid JSON.', $this->path));
                }
            });
        }

        return [];
    }

    /**
     * Determine if a local or remote file exists.
     *
     * @return bool
     */
    protected function fileExists(string $path)
    {
        return match (true) {
            str_starts_with($path, 'http://') => abort(1, 'Loading the configuration over plaintext HTTP is not allowed. Use HTTPS.'),
            str_starts_with($path, 'https://') => str_contains(get_headers($path)[0], '200 OK'),
            default => file_exists($path)
        };
    }

    /**
     * Resolve the file to extend.
     *
     * @param  array<string, array<int, string>|string>  $configuration
     * @return array<string, array<int, string>|string>
     */
    private function resolveExtend(array $configuration)
    {
        $extend = dirname($this->path).DIRECTORY_SEPARATOR.$configuration['extend'];

        $path = $this->isProjectConfiguration()
            ? $this->resolveProjectPath('extend', $extend)
            : realpath($extend);

        $extended = json_decode(file_get_contents($path), true);

        if (isset($extended['extend'])) {
            throw new \LogicException('Pint configuration cannot extend from more than 1 file.');
        }

        return array_replace_recursive($extended, $configuration);
    }

    /**
     * Determine if the configuration file is located inside the project.
     *
     * @return bool
     */
    protected function isProjectConfiguration()
    {
        if (is_null($this->path) || str_starts_with($this->path, 'https://')) {
            return false;
        }

        $root = Project::path();

        if (Path::isBasePath($root, Path::makeAbsolute($this->path, $root))) {
            return true;
        }

        $realPath = realpath($this->path);
        $realRoot = realpath($root);

        return $realPath !== false && $realRoot !== false && Path::isBasePath($realRoot, $realPath);
    }

    /**
     * Resolve the given configuration path, ensuring it is located inside the project.
     *
     * @return string
     */
    protected function resolveProjectPath(string $option, string $path)
    {
        $existing = Path::isAbsolute($path) ? $path : Project::path().DIRECTORY_SEPARATOR.$path;
        $missing = [];

        while (! file_exists($existing) && ! is_link($existing) && dirname($existing) !== $existing) {
            array_unshift($missing, basename($existing));

            $existing = dirname($existing);
        }

        $resolved = realpath($existing);
        $root = realpath(Project::path());

        if ($resolved === false || $root === false || in_array('..', $missing, true) || ! Path::isBasePath($root, $resolved)) {
            abort(1, sprintf('The [%s] path [%s] must be inside the project.', $option, $path));
        }

        return implode(DIRECTORY_SEPARATOR, [$resolved, ...$missing]);
    }

    /**
     * Determine if the given file is a PHP CS Fixer cache.
     *
     * @return bool
     */
    protected function isCache(string $path)
    {
        try {
            Cache::fromJson((string) file_get_contents($path));
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }
}
