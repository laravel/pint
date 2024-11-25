<?php

namespace App\Output\Concerns;

use PhpCsFixer\Error\Error;
use PhpCsFixer\Runner\Event\FileProcessed;

/**
 * @property \Symfony\Component\Console\Input\InputInterface $input
 * @property \Symfony\Component\Console\Output\OutputInterface $output
 */
trait InteractsWithSymbols
{
    /**
     * The list of status symbols.
     *
     * @var array<int, array<int|string, array<string, string>|string>>
     */
    protected $statuses = [
        FileProcessed::STATUS_INVALID => ['symbol' => '!', 'format' => '<options=bold;fg=red>%s</>'],
        FileProcessed::STATUS_SKIPPED => ['symbol' => '.', 'format' => '<fg=gray>%s</>'],
        FileProcessed::STATUS_NO_CHANGES => ['symbol' => '.', 'format' => '<fg=gray>%s</>'],
        FileProcessed::STATUS_FIXED => [
            ['symbol' => '⨯', 'format' => '<options=bold;fg=red>%s</>'],
            ['symbol' => '✓', 'format' => '<options=bold;fg=green>%s</>'],
        ],
        FileProcessed::STATUS_EXCEPTION => ['symbol' => '!', 'format' => '<options=bold;fg=red>%s</>'],
        FileProcessed::STATUS_LINT => ['symbol' => '!', 'format' => '<options=bold;fg=red>%s</>'],
    ];

    /**
     * Gets the symbol for the given status.
     *
     * @param  int  $status
     * @return string
     */
    public function getSymbol($status)
    {
        $statusSymbol = $this->statuses[$status];

        if (! isset($statusSymbol['symbol'])) {
            $statusSymbol = ($this->input->getOption('test') || $this->input->getOption('bail'))
                ? $statusSymbol[0]
                : $statusSymbol[1];
        }

        if ($this->output->isDecorated()) {
            return sprintf($statusSymbol['format'], (string) $statusSymbol['symbol']);
        }

        return (string) $statusSymbol['symbol'];
    }

    /**
     * Converts the given error type to a processed status.
     *
     * @param  int  $type
     * @return string
     */
    protected function getSymbolFromErrorType($type)
    {
        $status = match ($type) {
            Error::TYPE_INVALID => FileProcessed::STATUS_INVALID,
            Error::TYPE_EXCEPTION => FileProcessed::STATUS_EXCEPTION,
            Error::TYPE_LINT => FileProcessed::STATUS_LINT,
            default => FileProcessed::STATUS_INVALID,
        };

        return $this->getSymbol($status);
    }
}
