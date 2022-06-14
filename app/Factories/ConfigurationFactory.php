<?php

namespace App\Factories;

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

class ConfigurationFactory
{
    /**
     * The list of folders that should be considered for linting.
     *
     * @var array<int, string>
     */
    protected static $folders = [
        'app',
        'config',
        'database',
        'resources',
        'routes',
        'src',
        'tests',
    ];

    /**
     * Creates a configuration with the given preset of rules.
     *
     * @return \PhpCsFixer\ConfigInterface
     */
    public static function preset($rules)
    {
        $ins = collect(static::$folders)
            ->map(fn ($folder) => implode(DIRECTORY_SEPARATOR, [
                ConfigurationResolverFactory::$context['path'],
                $folder,
            ]))->filter(fn ($folder) => is_dir($folder))->values()->toArray();

        $finder = Finder::create()
            ->in($ins)
            ->name('*.php')
            ->notName('*.blade.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        return (new Config())
            ->setFinder($finder)
            ->setRules($rules)
            ->setRiskyAllowed(true)
            ->setUsingCache(true);
    }
}
