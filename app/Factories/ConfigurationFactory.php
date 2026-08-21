<?php

namespace App\Factories;

use App\BladeFormatter;
use App\Contracts\HasPrettierDependencies;
use App\Fixers\LaravelBlade\Fixer;
use App\Fixers\Prettier\CssFixer;
use App\Fixers\Prettier\JsFixer;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Prettier;
use PhpCsFixer\Config;
use PhpCsFixer\ConfigInterface;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

class ConfigurationFactory
{
    /**
     * The list of files to ignore.
     *
     * @var array<int, string>
     */
    protected static $notName = [
        '_ide_helper_actions.php',
        '_ide_helper_models.php',
        '_ide_helper.php',
        '.phpstorm.meta.php',
    ];

    /**
     * The list of folders to ignore.
     *
     * @var array<int, string>
     */
    protected static $exclude = [
        'bootstrap/cache',
        'build',
        'node_modules',
        'storage',
    ];

    /**
     * Creates a PHP CS Fixer Configuration with the given array of rules.
     *
     * @param  array<string, array<string, array<int|string, string|int|string[]>|bool|string>|bool>  $rules
     * @return ConfigInterface
     */
    public static function preset($rules)
    {
        return (new Config)
            ->setParallelConfig(ParallelConfigFactory::detect())
            ->setFinder(self::finder())
            ->setRules(array_merge($rules, resolve(ConfigurationJsonRepository::class)->rules()))
            ->setRiskyAllowed(true)
            ->setUsingCache(static::shouldUseCache())
            ->setUnsupportedPhpVersionAllowed(true)
            ->registerCustomFixers(self::customFixers());
    }

    /**
     * The list of custom fixers Pint registers.
     *
     * @return array<int, FixerInterface>
     */
    public static function customFixers()
    {
        return [
            new Fixer(resolve(BladeFormatter::class)),
            new JsFixer(resolve(Prettier::class)),
            new CssFixer(resolve(Prettier::class)),
        ];
    }

    /**
     * Creates the finder instance.
     *
     * @return Finder
     */
    public static function finder()
    {
        $localConfiguration = resolve(ConfigurationJsonRepository::class);

        $finder = Finder::create()
            ->notName(static::notName())
            ->exclude(static::$exclude)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        foreach (static::prettierNames() as $pattern) {
            $finder->name($pattern);
        }

        foreach ($localConfiguration->finder() as $method => $arguments) {
            if (! method_exists($finder, $method)) {
                abort(1, sprintf('Option [%s] is not valid.', $method));
            }

            $finder->{$method}($arguments);
        }

        return $finder;
    }

    /**
     * The list of files to ignore, accounting for the [Pint/Laravel_blade].
     *
     * @return array<int, string>
     */
    public static function notName()
    {
        $notName = static::$notName;

        if (static::shouldExcludeBladeFiles()) {
            $notName[] = '*.blade.php';
        }

        return $notName;
    }

    /**
     * Determine whether blade files should be excluded from formatting.
     *
     * @return bool
     */
    protected static function shouldExcludeBladeFiles()
    {
        $rules = resolve(ConfigurationJsonRepository::class)->rules();

        return ($rules['Pint/laravel_blade'] ?? false) === false;
    }

    /**
     * Determine whether the cache may be used.
     *
     * A rule whose output depends on the installed prettier dependencies cannot
     * be cached on file contents alone, so the cache is given up entirely when
     * any of those rules is enabled.
     *
     * @return bool
     */
    protected static function shouldUseCache()
    {
        $rules = resolve(ConfigurationJsonRepository::class)->rules();

        foreach (static::customFixers() as $fixer) {
            if ($fixer instanceof HasPrettierDependencies && ($rules[$fixer->getName()] ?? false) === true) {
                return false;
            }
        }

        return true;
    }

    /**
     * The finder name patterns required by the enabled prettier rules.
     *
     * @return array<int, string>
     */
    protected static function prettierNames()
    {
        $rules = resolve(ConfigurationJsonRepository::class)->rules();

        return collect(static::customFixers())
            ->filter(fn ($fixer) => $fixer instanceof HasPrettierDependencies)
            ->filter(fn ($fixer) => ($rules[$fixer->getName()] ?? false) === true)
            ->flatMap(fn (HasPrettierDependencies&FixerInterface $fixer) => $fixer->finderNames())
            ->values()
            ->all();
    }

    /**
     * Check if a file path should be excluded based on finder rules.
     */
    public static function isPathExcluded(string $filePath): bool
    {
        $localConfiguration = resolve(ConfigurationJsonRepository::class);
        $basePath = getcwd();

        $relativePath = str_starts_with($filePath, $basePath)
            ? substr($filePath, strlen($basePath) + 1)
            : $filePath;

        $relativePath = str_replace('\\', '/', $relativePath);
        $fileName = basename($filePath);

        foreach (static::notName() as $pattern) {
            if (fnmatch($pattern, $fileName)) {
                return true;
            }
        }

        foreach (static::$exclude as $excludedFolder) {
            $excludedFolder = str_replace('\\', '/', $excludedFolder);
            if (str_starts_with($relativePath, $excludedFolder.'/') || $relativePath === $excludedFolder) {
                return true;
            }
        }

        $finderConfig = $localConfiguration->finder();

        if (isset($finderConfig['notName'])) {
            foreach ((array) $finderConfig['notName'] as $pattern) {
                if (fnmatch($pattern, $fileName)) {
                    return true;
                }
            }
        }

        if (isset($finderConfig['exclude'])) {
            foreach ((array) $finderConfig['exclude'] as $excludedFolder) {
                $excludedFolder = str_replace('\\', '/', $excludedFolder);
                if (str_starts_with($relativePath, $excludedFolder.'/') || $relativePath === $excludedFolder) {
                    return true;
                }
            }
        }

        if (isset($finderConfig['notPath'])) {
            foreach ((array) $finderConfig['notPath'] as $pattern) {
                if (fnmatch($pattern, $relativePath)) {
                    return true;
                }
            }
        }

        return false;
    }
}
