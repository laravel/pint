<?php

namespace App\Factories;

use App\Repositories\ConfigurationJsonRepository;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use App\Project;

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
     * @param  array<int, object>  $fixers
     * @param  array<string, array<string, array<int|string, string|null>|bool|string>|bool>  $rules
     * @return \PhpCsFixer\ConfigInterface
     */
    public static function preset($fixers, $rules)
    {
        return (new Config())
            ->setFinder(self::finder())
            ->registerCustomFixers(array_merge($fixers, self::fixers()))
            ->setRules(array_merge($rules, resolve(ConfigurationJsonRepository::class)->rules()))
            ->setRiskyAllowed(true)
            ->setUsingCache(true);
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
     * Generates fixer classes.
     *
     * @return array<int, object>
     */
    public static function fixers()
    {
        $localConfiguration = resolve(ConfigurationJsonRepository::class);

        if(empty($localConfiguration->fixers())) {
            return [];
        };

        if(! file_exists(Project::path()."/vendor/autoload.php")) {
            abort(1, sprintf('Composer autoload file not found'));
        }

        require Project::path()."/vendor/autoload.php";

        $fixers = [];

        foreach( $localConfiguration->fixers() as $class )
        {
            $fixers[] = new $class();
        }

        return $fixers;
    }
}
