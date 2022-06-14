<?php

namespace App\Commands;

use App\Factories\ConfigurationResolverFactory;
use App\Output\Progress;
use ArrayIterator;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use PhpCsFixer\Console\Command\FixCommandExitStatusCalculator;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Error\ErrorsManager;
use PhpCsFixer\Runner\Runner;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use function Termwind\{render};

class LintCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected $name = 'lint';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Lints the project\'s code';

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
                    new InputArgument('path', InputArgument::OPTIONAL, 'The project\'s path.', (string) getcwd()),
                    new InputOption('risky', '', InputOption::VALUE_NONE, 'If risky fixers are allowed to be used.'),
                    new InputOption('dry-run', '', InputOption::VALUE_NONE, 'If the linter should run in "dry-run". '),
                ]
            );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $resolver = ConfigurationResolverFactory::fromIO($this->input, $this->output);

        $reporter = $resolver->getReporter();
        $finder = $resolver->getFinder();

        $errors = new ErrorsManager();
        $stopwatch = new Stopwatch();
        $eventDispatcher = new EventDispatcher();

        $finder = new ArrayIterator(iterator_to_array($finder));
        $progress = new Progress(
            $this->output,
            $eventDispatcher,
            count($finder)
        );

        $progress->subscribe();

        $runner = new Runner(
            $finder,
            $resolver->getFixers(),
            $resolver->getDiffer(),
            $eventDispatcher,
            $errors,
            $resolver->getLinter(),
            $resolver->isDryRun(),
            $resolver->getCacheManager(),
            $resolver->getDirectory(),
            $resolver->shouldStopOnViolation()
        );

        $stopwatch->start('fixFiles');
        $changed = $runner->fix();
        $stopwatch->stop('fixFiles');

        $fixEvent = $stopwatch->getEvent('fixFiles');

        $reportSummary = new ReportSummary(
            $changed,
            $fixEvent->getDuration(),
            $fixEvent->getMemory(),
            OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity(),
            $resolver->isDryRun(),
            $this->output->isDecorated()
        );

        $this->output->isDecorated()
            ? $this->output->write($reporter->generate($reportSummary))
            : $this->output->write($reporter->generate($reportSummary), false, OutputInterface::OUTPUT_RAW);

        $progress->unsubscribe();

        return $this->exit($resolver, $errors, $changed);
    }

    /**
     * Returns the command exit code based on the linting errors.
     *
     * @param  \PhpCsFixer\Console\ConfigurationResolver  $resolver
     * @param  \PhpCsFixer\Error\ErrorsManager  $errors
     * @param  array<int, string>  $changed
     * @return int
     */
    private function exit($resolver, $errors, array $changed): int
    {
        return (new FixCommandExitStatusCalculator())->calculate(
            $resolver->isDryRun(),
            count($changed) > 0,
            count($errors->getInvalidErrors()) > 0,
            count($errors->getExceptionErrors()) > 0,
            count($errors->getLintErrors()) > 0
        );
    }
}
