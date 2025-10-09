<?php

namespace App\Commands;

use App\Actions\FixCode;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

class DefaultCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Fix the coding style of the given path';

    /**
     * The configuration of the command.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(
                [
                    new InputArgument('path', InputArgument::IS_ARRAY, 'The path to fix', [(string) getcwd()]),
                    new InputOption('config', '', InputOption::VALUE_REQUIRED, 'The configuration that should be used'),
                    new InputOption('no-config', '', InputOption::VALUE_NONE, 'Disable loading any configuration file'),
                    new InputOption('preset', '', InputOption::VALUE_REQUIRED, 'The preset that should be used'),
                    new InputOption('test', '', InputOption::VALUE_NONE, 'Test for code style errors without fixing them'),
                    new InputOption('bail', '', InputOption::VALUE_NONE, 'Test for code style errors without fixing them and stop on first error'),
                    new InputOption('repair', '', InputOption::VALUE_NONE, 'Fix code style errors but exit with status 1 if there were any changes made'),
                    new InputOption('diff', '', InputOption::VALUE_REQUIRED, 'Only fix files that have changed since branching off from the given branch', null, ['main', 'master', 'origin/main', 'origin/master']),
                    new InputOption('dirty', '', InputOption::VALUE_NONE, 'Only fix files that have uncommitted changes'),
                    new InputOption('format', '', InputOption::VALUE_REQUIRED, 'The output format that should be used'),
                    new InputOption('output-to-file', '', InputOption::VALUE_REQUIRED, 'Output the test results to a file at this path'),
                    new InputOption('output-format', '', InputOption::VALUE_REQUIRED, 'The format that should be used when outputting the test results to a file'),
                    new InputOption('cache-file', '', InputArgument::OPTIONAL, 'The path to the cache file'),
                    new InputOption('parallel', 'p', InputOption::VALUE_NONE, 'Runs the linter in parallel (Experimental)'),
                    new InputOption('max-processes', null, InputOption::VALUE_REQUIRED, 'The number of processes to spawn when using parallel execution'),
                    new InputOption('stdin-filename', null, InputOption::VALUE_REQUIRED, 'Provide file path context for stdin input'),
                ],
            );
    }

    /**
     * Execute the console command.
     *
     * @param  \App\Actions\FixCode  $fixCode
     * @param  \App\Actions\ElaborateSummary  $elaborateSummary
     * @return int
     */
    public function handle($fixCode, $elaborateSummary)
    {
        if ($this->hasStdinInput()) {
            return $this->fixStdinInput($fixCode);
        }

        [$totalFiles, $changes] = $fixCode->execute();

        return $elaborateSummary->execute($totalFiles, $changes);
    }

    /**
     * Fix the code sent to Pint on stdin and output to stdout.
     *
     * The stdin-filename option provides file path context for error messages.
     * Falls back to 'stdin.php' if not provided.
     */
    protected function fixStdinInput(FixCode $fixCode): int
    {
        $contextPath = $this->option('stdin-filename') ?: 'stdin.php';
        $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pint_stdin_'.uniqid().'.php';

        $this->input->setArgument('path', [$tempFile]);
        $this->input->setOption('format', 'json');

        try {
            file_put_contents($tempFile, stream_get_contents(STDIN));
            $fixCode->execute();
            fwrite(STDOUT, file_get_contents($tempFile));

            return self::SUCCESS;
        } catch (Throwable $e) {
            fwrite(STDERR, "pint: error processing {$contextPath}: {$e->getMessage()}\n");

            return self::FAILURE;
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Determine if there is input available on stdin.
     *
     * Stdin mode is triggered by either:
     * - Passing '-' as the path argument (Unix convention like Black, cat)
     * - Providing the --stdin-filename option (editor-friendly like Prettier)
     */
    protected function hasStdinInput(): bool
    {
        $paths = $this->argument('path');

        $hasStdinPlaceholder = ! empty($paths) && $paths[0] === '__STDIN_PLACEHOLDER__';
        $hasStdinFilename = ! empty($this->option('stdin-filename'));

        return $hasStdinPlaceholder || $hasStdinFilename;
    }
}
