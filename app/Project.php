<?php

namespace App;

use App\Contracts\PathsRepository;

class Project
{
    /**
     * Determine the project paths to apply the code style based on the options and arguments passed.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return array<int, string>
     */
    public static function paths($input)
    {
        if ($input->getOption('dirty')) {
            return static::resolveDirtyPaths();
        }

        if ($input->getOption('staged')) {
           return static::resolveStagedPaths();
        }

        return $input->getArgument('path');
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

    /**
     * Resolves the dirty paths, if any.
     *
     * @return array<int, string>
     */
    public static function resolveDirtyPaths()
    {
        $files = app(PathsRepository::class)->dirty();

        if (empty($files)) {
            abort(0, 'No dirty files found.');
        }

        return $files;
    }

    /**
     * Resolves the staged paths, if any.
     *
     * @return array<int, string>
     */
    public static function resolveStagedPaths()
    {
        $files = app(PathsRepository::class)->staged();

        if (empty($files)) {
            abort(0, 'No staged files found.');
        }

        return $files;
    }
}
