<?php

namespace App\Factories;

use App\Repositories\ConfigurationJsonRepository;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
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
        '*.blade.php',
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
     * @return \PhpCsFixer\ConfigInterface
     */
    public static function preset($rules)
    {
        return (new Config) // @phpstan-ignore-line
            ->setParallelConfig(ParallelConfigFactory::detect())
            ->setFinder(self::finder())
            ->setRules(array_merge($rules, resolve(ConfigurationJsonRepository::class)->rules()))
            ->setRiskyAllowed(true)
            ->setUsingCache(true)
            ->setUnsupportedPhpVersionAllowed(true);
    }

    /**
     * Creates the finder instance.
     *
     * @return \PhpCsFixer\Finder
     */
    public static function finder()
    {
        $localConfiguration = resolve(ConfigurationJsonRepository::class);

        $finder = Finder::create()
            ->notName(static::$notName)
            ->exclude(static::$exclude)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        foreach ($localConfiguration->finder() as $method => $arguments) {
            if (! method_exists($finder, $method)) {
                abort(1, sprintf('Option [%s] is not valid.', $method));
            }

            $finder->{$method}($arguments);
        }

        return $finder;
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

        foreach (static::$notName as $pattern) {
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
