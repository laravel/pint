<?php

namespace App\Output;

use App\Output\Concerns\InteractsWithSymbols;
use PhpCsFixer\FixerFileProcessedEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Progress
{
    use InteractsWithSymbols;

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

        $this->output->write($this->getSymbol($event->getStatus()));

        $this->processed++;
    }
}
