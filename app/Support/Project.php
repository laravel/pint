<?php

namespace App\Support;

class Project
{
    /**
     * Determine the project paths to apply the code style
     * based on the options and arguments passed.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return array<string>
     */
    public static function paths(\Symfony\Component\Console\Input\InputInterface $input): array
    {
        if (! $input->getOption('dirty')) {
            return $input->getArgument('path');
        }

        [$files, $successful] = \Facades\App\Support\Git::dirtyFiles();

        if (! $successful) {
            abort(1, 'Option [dirty] must be used within a Git repository.');
        }

        if (empty($files)) {
            abort(1, 'No dirty files found.');
        }

        return $files;
    }

    /**
     * The project being analysed path.
     *
     * @return string
     */
    public static function path()
    {
        return getcwd();
    }
}
