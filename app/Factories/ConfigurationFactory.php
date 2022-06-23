<?php

namespace App\Factories;

use App\Fixers\LaravelPhpdocAlignmentFixer;
use App\Fixers\LaravelPhpdocOrderFixer;
use App\Fixers\LaravelPhpdocSeparationFixer;
use App\Repositories\ConfigurationJsonRepository;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

class ConfigurationFactory
{
    /**
     * The list of files to ignore.
     *
     * @var array<int, string>
     */
    protected static $notName = [
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
        'storage',
        'bootstrap/cache',
    ];

    /**
     * Creates a PHP CS Fixer Configuration with the given array of rules.
     *
     * @param  array<string, array<string, array<int|string, string|null>|bool|string>|bool>  $rules
     * @return \PhpCsFixer\ConfigInterface
     */
    public static function preset($rules)
    {
        $path = ConfigurationResolverFactory::$context['path'];
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

        return (new Config())
            ->setFinder($finder)
            ->setRules(array_merge($rules, $localConfiguration->rules()))
            ->setRiskyAllowed(true)
            ->setUsingCache(true)
            ->registerCustomFixers([
                new LaravelPhpdocOrderFixer(),
                new LaravelPhpdocSeparationFixer(),
                new LaravelPhpdocAlignmentFixer(),
            ]);
    }
}
