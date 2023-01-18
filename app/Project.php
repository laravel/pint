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
            return static::resolveDirtyPaths(
                $input->getOption('ignore-no-changes')
            );
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
     * @param  bool  $ignoreNoChanges
     * @return array<int, string>
     */
    public static function resolveDirtyPaths($ignoreNoChanges)
    {
        $files = app(PathsRepository::class)->dirty();

        if (empty($files) && !$ignoreNoChanges) {
            abort(1, 'No dirty files found.');
        }

        return $files;
    }
}
