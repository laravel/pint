<?php

namespace App\Factories;

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
        '*.blade.php',
    ];

    /**
     * The list of folders to ignore.
     *
     * @var array<int, string>
     */
    protected static $exclude = [
        'storage',
    ];

    /**
     * Creates a configuration with the given preset of rules.
     *
     * @param  array<int, string>  $rules
     * @return \PhpCsFixer\ConfigInterface
     */
    public static function preset($rules)
    {
        $path = ConfigurationResolverFactory::$context['path'];

        $finder = Finder::create()
            ->in($path)
            ->notName(static::$notName)
            ->exclude(static::$exclude)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        foreach ((new ConfigurationJsonRepository($path))->get() as $method => $arguments) {
            if (! method_exists($finder, $method)) {
                abort(1, sprintf('Option [%s] is not valid.', $method));
            }

            $finder->{$method}($arguments);
        }

        return (new Config())
            ->setFinder($finder)
            ->setRules($rules)
            ->setRiskyAllowed(true)
            ->setUsingCache(true);
    }
}
