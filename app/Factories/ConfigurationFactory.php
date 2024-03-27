<?php

namespace App\Factories;

use App\BladeFormatter;
use App\Fixers;
use App\Fixers\LaravelBlade\Fixer;
use App\Repositories\ConfigurationJsonRepository;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use Symfony\Component\Console\Input\InputInterface;

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
     * @param  array<string, array<string, array<int|string, string|null>|bool|string>|bool>  $rules
     * @return \PhpCsFixer\ConfigInterface
     */
    public static function preset($rules)
    {
        return (new Config())
            ->setFinder(self::finder())
            ->setRules(array_merge($rules, resolve(ConfigurationJsonRepository::class)->rules()))
            ->setRiskyAllowed(true)
            ->setUsingCache(true)
            ->registerCustomFixers([
                new Fixer(resolve(BladeFormatter::class)),
            ]);
    }

    /**
     * Creates the finder instance.
     *
     * @return \PhpCsFixer\Finder
     */
    public static function finder()
    {
        $localConfiguration = resolve(ConfigurationJsonRepository::class);

        if (resolve(InputInterface::class)->getOption('blade') === false) {
            $notName = array_merge(static::$notName, ['*.blade.php']);
        }

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
