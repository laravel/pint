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
     * Statically holds the resolver context.
     *
     * @var array<string, string>
     */
    public static $context = [];

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

        static::$context = ['path' => $path];

        return new ConfigurationResolver(
            new Config('default'),
            [
                'config' => implode(DIRECTORY_SEPARATOR, [
                    dirname(__DIR__, 2),
                    'resources',
                    'presets',
                    'recommended.php',
                ]),
                'dry-run'     => $input->getOption('dry-run'),
                'path-mode'   => ConfigurationResolver::PATH_MODE_OVERRIDE,
                'cache-file'  => implode(DIRECTORY_SEPARATOR, [
                    sys_get_temp_dir(),
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
