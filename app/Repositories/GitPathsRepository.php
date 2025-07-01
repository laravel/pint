<?php

namespace App\Repositories;

use App\Contracts\PathsRepository;
use App\Factories\ConfigurationFactory;
use Illuminate\Support\Collection;
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
        $process = tap(new Process(['git', 'diff', '--name-only', 'HEAD', '--', '**.php']))->run();

        if (! $process->isSuccessful()) {
            abort(1, 'The [--dirty] option is only available when using Git.');
        }

        $dirtyFiles = collect(preg_split('/\R+/', $process->getOutput(), flags: PREG_SPLIT_NO_EMPTY))
            ->values()
            ->map(fn ($s) => (string) $s);

        return $this->processFileNames($dirtyFiles);
    }

    /**
     * {@inheritDoc}
     */
    public function diff($branch)
    {
        $files = [
            'committed' => tap(new Process(['git', 'diff', '--name-only', '--diff-filter=AM', "{$branch}...HEAD", '--', '**.php']))->run(),
            'staged' => tap(new Process(['git', 'diff', '--name-only', '--diff-filter=AM', '--cached', '--', '**.php']))->run(),
            'unstaged' => tap(new Process(['git', 'diff', '--name-only', '--diff-filter=AM', '--', '**.php']))->run(),
            'untracked' => tap(new Process(['git', 'ls-files', '--others', '--exclude-standard', '--', '**.php']))->run(),
        ];

        /** @var Collection<int, string> $files */
        $files = collect($files)
            ->each(fn ($process) => abort_if(
                boolean: ! $process->isSuccessful(),
                code: 1,
                message: 'The [--diff] option is only available when using Git.',
            ))
            ->map(fn ($process) => preg_split('/\R+/', $process->getOutput(), flags: PREG_SPLIT_NO_EMPTY))
            ->flatten()
            ->unique()
            ->values()
            ->map(fn ($s) => (string) $s);

        return $this->processFileNames($files);
    }

    /**
     * Process the files.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $fileNames
     * @return array<int, string>
     */
    protected function processFileNames(Collection $fileNames)
    {
        $gitRoot = trim(tap(new Process(['git', 'rev-parse', '--show-toplevel']))->run()->getOutput());

        $processedFileNames = $fileNames
            ->map(function ($file) use ($gitRoot) {
                $absolutePath = $gitRoot.DIRECTORY_SEPARATOR.$file;

                if (PHP_OS_FAMILY === 'Windows') {
                    $absolutePath = str_replace('/', DIRECTORY_SEPARATOR, $absolutePath);
                }

                return $absolutePath;
            })
            ->all();

        $files = array_values(array_map(function ($splFile) {
            return $splFile->getPathname();
        }, iterator_to_array(
            ConfigurationFactory::finder()
                ->in($this->path)
                ->files()
        )));

        return array_values(array_intersect($files, $processedFileNames));
    }
}
