<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Runner\Parallel;

/**
 * Copyright (c) 2012+ Fabien Potencier, Dariusz Rumiński
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

use PhpCsFixer\Runner\RunnerConfig;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * This overrides the default "ProcessFactory" to allow for
 * customization of the command-line arguments that better
 * suit the needs of the laravel pint package.
 *
 * @author Greg Korba <greg@codito.dev>
 *
 * @readonly
 *
 * @internal
 */
final class ProcessFactory
{
    private InputInterface $input;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    public function create(
        LoopInterface $loop,
        RunnerConfig $runnerConfig,
        ProcessIdentifier $identifier,
        int $serverPort
    ): Process {
        $commandArgs = $this->getCommandArgs($serverPort, $identifier, $runnerConfig);

        return new Process(
            implode(' ', $commandArgs),
            $loop,
            $runnerConfig->getParallelConfig()->getProcessTimeout()
        );
    }

    /**
     * @private
     *
     * @return list<string>
     */
    public function getCommandArgs(int $serverPort, ProcessIdentifier $identifier, RunnerConfig $runnerConfig): array
    {
        $phpBinary = (new PhpExecutableFinder)->find(false);

        if ($phpBinary === false) {
            throw new ParallelisationException('Cannot find PHP executable.');
        }

        $mainScript = $_SERVER['argv'][0];

        $commandArgs = [
            escapeshellarg($phpBinary),
            escapeshellarg($mainScript),
            'worker',
            '--port',
            (string) $serverPort,
            '--identifier',
            escapeshellarg($identifier->toString()),
        ];

        if ($runnerConfig->isDryRun()) {
            $commandArgs[] = '--dry-run';
        }

        if (filter_var($this->input->getOption('diff'), FILTER_VALIDATE_BOOLEAN)) {
            $commandArgs[] = '--diff';
        }

        if (filter_var($this->input->getOption('stop-on-violation'), FILTER_VALIDATE_BOOLEAN)) {
            $commandArgs[] = '--stop-on-violation';
        }

        foreach (['allow-risky', 'config', 'rules', 'using-cache', 'cache-file'] as $option) {
            $optionValue = $this->input->getOption($option);

            if ($optionValue !== null) {
                $commandArgs[] = "--{$option}";
                $commandArgs[] = escapeshellarg($optionValue);
            }
        }

        return $commandArgs;
    }
}
