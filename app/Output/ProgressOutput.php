<?php

namespace App\Output;

use App\Output\Concerns\InteractsWithSymbols;
use PhpCsFixer\Runner\Event\FileProcessed;
use Symfony\Component\Console\Terminal;

class ProgressOutput
{
    use InteractsWithSymbols;

    /**
     * Holds the current number of processed files.
     *
     * @var int
     */
    protected $processed = 0;

    /**
     * Holds the number of symbols on the current terminal line.
     *
     * @var int
     */
    protected $symbolsPerLine = 0;

    /**
     * Creates a new Progress Output instance.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Symfony\Component\EventDispatcher\EventDispatcherInterface  $dispatcher
     * @return void
     */
    public function __construct(
        protected $dispatcher,
        protected $input,
        protected $output,
    ) {
        $this->symbolsPerLine = (new Terminal)->getWidth() - 4;
    }

    /**
     * Subscribes for file processed events.
     *
     * @return void
     */
    public function subscribe()
    {
        $this->dispatcher->addListener(FileProcessed::NAME, [$this, 'handle']);
    }

    /**
     * Stops the file processed event subscription.
     *
     * @return void
     */
    public function unsubscribe()
    {
        $this->dispatcher->removeListener(FileProcessed::NAME, [$this, 'handle']);
    }

    /**
     * Handle the given processed file event.
     *
     * @param  \PhpCsFixer\Runner\Event\FileProcessed  $event
     * @return void
     */
    public function handle($event)
    {
        $symbolsOnCurrentLine = $this->processed % $this->symbolsPerLine;

        if ($symbolsOnCurrentLine >= (new Terminal)->getWidth() - 4) {
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
