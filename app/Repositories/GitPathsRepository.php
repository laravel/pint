<?php

namespace App\Repositories;

use App\Contracts\PathsRepository;
use App\Factories\ConfigurationFactory;
use Symfony\Component\Process\Process;

class GitPathsRepository implements PathsRepository
{
    /**
     * The project path.
     *
     * @var string
     */
    protected $path;

    /**
     * Creates a new Paths Repository instance.
     *
     * @param  string  $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     */
    public function dirty()
    {
        // Get ready-to-use paths of files that were added, modified, moved or renamed
        $process = tap(new Process(['git', 'diff', 'HEAD', '--name-only', '--diff-filter=ACMR', '--', '*.php']))->run();

        if (! $process->isSuccessful()) {
            abort(1, 'The [--dirty] option is only available when using Git.');
        }

        $dirtyFiles = collect(explode("\n", $process->getOutput()))
            ->filter()
            ->map(fn ($file) => $this->path.DIRECTORY_SEPARATOR.$file)
            ->values()
            ->all();

        $files = array_values(array_map(function ($splFile) {
            return $splFile->getPathname();
        }, iterator_to_array(ConfigurationFactory::finder()
            ->in($this->path)
            ->files()
        )));

        return array_values(array_intersect($files, $dirtyFiles));
    }
}
