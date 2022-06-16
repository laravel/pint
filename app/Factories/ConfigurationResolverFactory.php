<?php

namespace App\Factories;

use PhpCsFixer\Config;
use PhpCsFixer\ConfigInterface;
use PhpCsFixer\Console\ConfigurationResolver;
use PhpCsFixer\Finder;
use PhpCsFixer\ToolInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @return \PhpCsFixer\Console\ConfigurationResolver
     */
    public static function fromIO($input, $output)
    {
        $path = (string) $input->getArgument('path');
        $preset = (string) $input->getOption('preset');

        if (! in_array($preset, static::$presets)) {
            abort(1, 'Preset not found.');
        }

        static::$context = ['path' => $path];

        return new ConfigurationResolver(
            new Config('default'),
            [
                'config' => implode(DIRECTORY_SEPARATOR, [
                    dirname(__DIR__, 2),
                    'resources',
                    'presets',
                    sprintf('%s.php', $preset),
                ]),
                'diff' => true,
                'dry-run'     => ! $input->getOption('fix'),
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
    }
}
