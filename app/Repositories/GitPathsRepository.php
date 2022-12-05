<?php

namespace App\Repositories;

use App\Contracts\PathsRepository;
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
        $process = tap(
            new Process(
                ['git', 'diff', '--name-only', '--diff-filter=AMCR', 'HEAD', '--', '*.php'],
                $this->path,
            )
        )->run();

        if (! $process->isSuccessful()) {
            abort(1, 'The [--dirty] option is only available when using Git.');
        }

        return preg_split('/\R+/', $process->getOutput(), flags: PREG_SPLIT_NO_EMPTY);
    }
}
