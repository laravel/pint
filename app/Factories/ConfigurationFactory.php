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
}
