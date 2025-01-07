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

        if ($diff = $input->getOption('diff')) {
            return static::resolveDiffPaths($diff);
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
     * Resolves the paths that have changed since branching off from the given branch, if any.
     *
     * @param  string  $branch
     * @return array<int, string>
     */
    public static function resolveDiffPaths($branch)
    {
        $files = app(PathsRepository::class)->diff($branch);

        if (empty($files)) {
            abort(0, "No files have changed since branching off of {$branch}.");
        }

        return $files;
    }
}
