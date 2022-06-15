<?php

namespace App\Commands;

use App\Factories\ConfigurationResolverFactory;
use App\Output\Progress;
use App\Output\Summary;
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
     * Creates a new command instance.
     */
    public function __construct(
        protected ErrorsManager $errorsManager,
        protected Stopwatch $stopwatch,
        protected EventDispatcher $eventDispatcher,
    ) {
        parent::__construct();
    }

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
                    new InputOption('preset', '', InputOption::VALUE_REQUIRED, 'The preset that should be used', 'psr12'),
                    new InputOption('risky', '', InputOption::VALUE_NONE, 'If risky fixers are allowed to be used.'),
                    new InputOption('fix', '', InputOption::VALUE_NONE, 'If the linter should apply the fixes. '),
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

        $finder = new ArrayIterator(iterator_to_array($finder));
        $progress = new Progress(
            $this->input,
            $this->output,
            $this->eventDispatcher,
            count($finder)
        );

        $progress->subscribe();

        $runner = new Runner(
            $finder,
            $resolver->getFixers(),
            $resolver->getDiffer(),
            $this->eventDispatcher,
            $this->errorsManager,
            $resolver->getLinter(),
            $resolver->isDryRun(),
            $resolver->getCacheManager(),
            $resolver->getDirectory(),
            $resolver->shouldStopOnViolation()
        );

        $this->stopwatch->start('fixFiles');
        $changed = $runner->fix();
        $this->stopwatch->stop('fixFiles');

        $fixEvent = $this->stopwatch->getEvent('fixFiles');

        $reportSummary = new ReportSummary(
            $changed,
            $fixEvent->getDuration(),
            $fixEvent->getMemory(),
            OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity(),
            $resolver->isDryRun(),
            $this->output->isDecorated()
        );

        $progress->unsubscribe();

        (new Summary(
            $this->input,
            $this->output,
        ))->handle($reportSummary, $this->input->getArgument('path'), count($finder));

        return $this->exit($resolver, $this->errorsManager, $changed);
    }

    /**
     * Returns the command exit code based on the linting errors.
     *
     * @param  \PhpCsFixer\Console\ConfigurationResolver  $resolver
     * @param  \PhpCsFixer\Error\ErrorsManager  $errors
     * @param  array<int, string>  $changed
     * @return int
     */
    private function exit($resolver, $errors, $changed)
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
