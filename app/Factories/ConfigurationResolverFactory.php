<?php

namespace App\Factories;

use App\Repositories\ConfigurationJsonRepository;
use ArrayIterator;
use PhpCsFixer\Config;
use PhpCsFixer\Console\ConfigurationResolver;
use PhpCsFixer\ToolInfo;

class ConfigurationResolverFactory
{
    /**
     * Statically holds the resolver factory context.
     *
     * @var array<string, string>
     */
    public static $context = [];

    /**
     * The list of available presets.
     *
     * @var array<int, string>
     */
    public static $presets = [
        'laravel',
        'psr12',
    ];

    /**
     * Creates a new configuration resolver instance.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return array{\PhpCsFixer\Console\ConfigurationResolver, int}
     */
    public static function fromIO($input, $output)
    {
        $path = (string) $input->getArgument('path');

        $preset = resolve(ConfigurationJsonRepository::class)->preset();

        if (! in_array($preset, static::$presets)) {
            abort(1, 'Preset not found.');
        }

        static::$context = ['path' => $path];

        $resolver = new ConfigurationResolver(
            new Config('default'),
            [
                'config' => implode(DIRECTORY_SEPARATOR, [
                    dirname(__DIR__, 2),
                    'resources',
                    'presets',
                    sprintf('%s.php', $preset),
                ]),
                'diff' => true,
                'dry-run'     => $input->getOption('test'),
                'path-mode'   => ConfigurationResolver::PATH_MODE_OVERRIDE,
                'cache-file'  => implode(DIRECTORY_SEPARATOR, [
                    realpath(sys_get_temp_dir()),
                    md5($path),
                ]),
                'stop-on-violation' => false,
                'verbosity'         => $output->getVerbosity(),
                'show-progress'     => 'true',
            ],
            $path,
            new ToolInfo(),
        );

        $totalFiles = count(new ArrayIterator(iterator_to_array(
            $resolver->getFinder(),
        )));

        return [$resolver, $totalFiles];
    }
}
