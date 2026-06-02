<?php

namespace App\Output;

use App\Output\Concerns\InteractsWithSymbols;
use PhpCsFixer\Runner\Event\FileProcessed;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @param  EventDispatcherInterface  $dispatcher
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
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
     * @param  FileProcessed  $event
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
