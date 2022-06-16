<?php

namespace App\Output;

use PhpCsFixer\FixerFileProcessedEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Progress
{
    /**
     * The list of status symbols.
     *
     * @var array<int, array<'symbol'|'format', array<int, string>|string>>
     */
    protected static $status = [
        FixerFileProcessedEvent::STATUS_UNKNOWN    => ['symbol' => '?', 'format' => '<options=bold;fg=yellow>%s</>'],
        FixerFileProcessedEvent::STATUS_INVALID    => ['symbol' => '!', 'format' => '<options=bold;fg=red>%s</>'],
        FixerFileProcessedEvent::STATUS_SKIPPED    => ['symbol' => '.', 'format' => '<fg=green>%s</>'],
        FixerFileProcessedEvent::STATUS_NO_CHANGES => ['symbol' => '.', 'format' => '<fg=green>%s</>'],
        FixerFileProcessedEvent::STATUS_FIXED      => ['symbol' => 'F', 'format' => [
            '<options=bold;fg=red>%s</>', '<options=bold;fg=green>%s</>',
        ]],
        FixerFileProcessedEvent::STATUS_EXCEPTION  => ['symbol' => '!', 'format' => '<options=bold;fg=red>%s</>'],
        FixerFileProcessedEvent::STATUS_LINT       => ['symbol' => '!', 'format' => '<options=bold;fg=red>%s</>'],
    ];

    /**
     * Holds the current number of processed total.
     *
     * @var int
     */
    protected $processed = 0;

    /**
     * Holds the current number of symbols per line.
     *
     * @var int
     */
    protected $symbolsPerLine = 0;

    /**
     * Creates a new linting progress instance.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Symfony\Component\EventDispatcher\EventDispatcherInterface  $dispatcher
     * @param  int  $total
     * @return void
     */
    public function __construct(
        protected $input,
        protected $output,
        protected $dispatcher,
        protected $total
    ) {
        $this->symbolsPerLine = (new Terminal())->getWidth() - 4;
    }

    /**
     * Listen for fixed files.
     *
     * @return void
     */
    public function subscribe()
    {
        $this->dispatcher->addListener(FixerFileProcessedEvent::NAME, [$this, 'handle']);
    }

    /**
     * Stops listen for fixed files.
     *
     * @return void
     */
    public function unsubscribe()
    {
        $this->dispatcher->removeListener(FixerFileProcessedEvent::NAME, [$this, 'handle']);
    }

    /**
     * Handle the given processed event.
     *
     * @param  \PhpCsFixer\FixerFileProcessedEvent  $event
     * @return void
     */
    public function handle($event)
    {
        $symbolsOnCurrentLine = $this->processed % $this->symbolsPerLine;

        if ($symbolsOnCurrentLine >= (new Terminal())->getWidth() - 4) {
            $symbolsOnCurrentLine = 0;
        }

        if ($symbolsOnCurrentLine === 0) {
            $this->output->writeln('');
            $this->output->write('  ');
        }

        $status = self::$status[$event->getStatus()];

        $symbol = (string) $status['symbol'];

        if (! $this->output->isDecorated()) {
            $this->output->write($symbol);
        } else {
            if (is_array($status['format'])) {
                [$dryRunFormat, $fixFormat] = $status['format'];

                if ($this->input->getOption('fix')) {
                    $this->output->write(sprintf($fixFormat, $symbol));
                } else {
                    $this->output->write(sprintf($dryRunFormat, $symbol));
                }
            } else {
                $this->output->write(sprintf($status['format'], $symbol));
            }
        }

        $this->processed++;
    }
}
