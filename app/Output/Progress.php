<?php

namespace App\Output;

use PhpCsFixer\FixerFileProcessedEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Progress
{
    /**
     * The list of status symbols.
     *
     * @param array<string, array<string, string>>
     */
    private static $status = [
        FixerFileProcessedEvent::STATUS_UNKNOWN    => ['symbol' => '?', 'format' => '<options=bold;fg=yellow>%s</>'],
        FixerFileProcessedEvent::STATUS_INVALID    => ['symbol' => '!', 'format' => '<options=bold;fg=red>%s</>'],
        FixerFileProcessedEvent::STATUS_SKIPPED    => ['symbol' => '.', 'format' => '<options=bold;fg=green>%s</>'],
        FixerFileProcessedEvent::STATUS_NO_CHANGES => ['symbol' => '.', 'format' => '<options=bold;fg=green>%s</>'],
        FixerFileProcessedEvent::STATUS_FIXED      => ['symbol' => 'F', 'format' => '<options=bold;fg=red>%s</>'],
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
     */
    public function __construct(
        protected OutputInterface $output,
        protected EventDispatcherInterface $dispatcher,
        protected int $total
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
     * @param  \PhpCsFixer\FixerFileProcessedEvent
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

        $this->output->write($this->output->isDecorated() ? sprintf($status['format'], $status['symbol']) : $status['symbol']);

        $this->processed++;
    }
}
