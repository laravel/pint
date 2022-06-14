<?php

use App\Factories\ConfigurationResolverFactory;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    '@PSR12' => true,
];

$path = ConfigurationResolverFactory::$context['path'];

$ins = array_filter(array_map(function ($folder) use ($path) {
    return implode(DIRECTORY_SEPARATOR, [
        ConfigurationResolverFactory::$context['path'],
        $folder,
    ]);
}, [
    'app',
    'config',
    'database',
    'resources',
    'routes',
    'src',
    'tests',
]), function ($fullPath) {
    return file_exists($fullPath);
});

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
