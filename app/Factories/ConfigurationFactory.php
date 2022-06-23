<?php

namespace App\Factories;

use App\Fixers\LaravelPhpdocAlignmentFixer;
use App\Fixers\LaravelPhpdocOrderFixer;
use App\Fixers\LaravelPhpdocSeparationFixer;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Project;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\FixerInterface;

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
        'node_modules',
    ];

    /**
     * Creates a PHP CS Fixer Configuration with the given array of rules.
     *
     * @param  array<string, array<string, array<int|string, string|null>|bool|string>|bool>  $rules
     * @return \PhpCsFixer\ConfigInterface
     */
    public static function preset($rules)
    {
        $path = Project::path();
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

        $fixers = array_map(fn (string $fixer) => new $fixer(), $localConfiguration->fixers());

        $rules = array_merge(
            array_fill_keys(array_map(fn (FixerInterface $fixer) => $fixer->getName(), $fixers), true),
            $rules,
            $localConfiguration->rules(),
        );

        return (new Config())
            ->setFinder($finder)
            ->registerCustomFixers($fixers)
            ->setRules($rules)
            ->setRiskyAllowed(true)
            ->setUsingCache(true)
            ->registerCustomFixers([
                new LaravelPhpdocOrderFixer(),
                new LaravelPhpdocSeparationFixer(),
                new LaravelPhpdocAlignmentFixer(),
            ]);
    }
}
