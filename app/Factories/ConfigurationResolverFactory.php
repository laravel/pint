<?php

namespace App\Factories;

use App\Project;
use App\Repositories\ConfigurationJsonRepository;
use App\Support\Prettier;
use ArrayIterator;
use Laravel\AgentDetector\AgentDetector;
use PhpCsFixer\Config;
use PhpCsFixer\Console\ConfigurationResolver;
use PhpCsFixer\ToolInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationResolverFactory
{
    /**
     * The list of available presets.
     *
     * @var array<int, string>
     */
    public static $presets = [
        'laravel',
        'per',
        'psr12',
        'symfony',
        'empty',
    ];

    /**
     * Creates a new PHP CS Fixer Configuration Resolver instance
     * from the given input and output.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return array{ConfigurationResolver, int}
     */
    public static function fromIO($input, $output)
    {
        $localConfiguration = resolve(ConfigurationJsonRepository::class);

        $path = Project::paths($input);

        if ($localConfiguration->hasIncludedPaths() && $path === [(string) Project::path()]) {
            $path = [];
        }

        $preset = $localConfiguration->preset();

        if (! in_array($preset, static::$presets)) {
            abort(1, 'Preset not found.');
        }

        $resolver = new ConfigurationResolver(
            new Config('default'),
            [
                'allow-risky' => 'yes',
                'config' => implode(DIRECTORY_SEPARATOR, [
                    dirname(__DIR__, 2),
                    'resources',
                    'presets',
                    sprintf('%s.php', $preset),
                ]),
                'diff' => $output->isVerbose(),
                'dry-run' => $input->getOption('test') || $input->getOption('bail'),
                'path' => $path,
                'path-mode' => ConfigurationResolver::PATH_MODE_OVERRIDE,
                'cache-file' => static::ensureCacheFileIsSafeToWrite(
                    $input->getOption('cache-file') ?? $localConfiguration->cacheFile() ?? implode(DIRECTORY_SEPARATOR, [
                        realpath(sys_get_temp_dir()),
                        md5(
                            app()->isProduction()
                            ? (implode('|', $path).'||'.(string) Prettier::VERSION)
                            : (string) microtime()
                        ),
                    ]),
                ),
                'stop-on-violation' => $input->getOption('bail'),
                'verbosity' => $output->getVerbosity(),
                'show-progress' => 'true',
            ],
            Project::path(),
            new ToolInfo,
        );

        $totalFiles = count(new ArrayIterator(iterator_to_array(
            $resolver->getFinder(),
        )));

        return [$resolver, $totalFiles];
    }

    /**
     * Ensure the given cache file does not overwrite an unrelated file.
     *
     * @param  string  $cacheFile
     * @return string
     */
    protected static function ensureCacheFileIsSafeToWrite($cacheFile)
    {
        if (! file_exists($cacheFile)) {
            return $cacheFile;
        }

        if (is_dir($cacheFile)) {
            abort(1, sprintf('The cache file [%s] is a directory.', $cacheFile));
        }

        if (! is_file($cacheFile)) {
            return $cacheFile;
        }

        $contents = @file_get_contents($cacheFile);

        if ($contents === false) {
            abort(1, sprintf('The cache file [%s] could not be read.', $cacheFile));
        }

        if (trim($contents) === '') {
            return $cacheFile;
        }

        if (! static::isCache($contents)) {
            abort(1, sprintf('The cache file [%s] already exists and is not a Pint cache file. Refusing to overwrite it.', $cacheFile));
        }

        return $cacheFile;
    }

    /**
     * Determine if the given contents hold a PHP CS Fixer cache.
     *
     * @param  string  $contents
     * @return bool
     */
    protected static function isCache($contents)
    {
        return str_starts_with($contents, '{"php":');
    }

    /**
     * Determine if Pint is being run by an AI agent.
     */
    public static function runningInAgent(): bool
    {
        return AgentDetector::detect()->isAgent;
    }
}
