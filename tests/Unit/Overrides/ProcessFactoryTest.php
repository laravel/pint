<?php

use Illuminate\Support\ProcessUtils;
use PhpCsFixer\Runner\Parallel\ParallelConfig;
use PhpCsFixer\Runner\Parallel\ProcessFactory;
use PhpCsFixer\Runner\Parallel\ProcessIdentifier;
use PhpCsFixer\Runner\RunnerConfig;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

// The "pint" binary swaps this override in through a class map, so load it before the vendor class can autoload.
require_once dirname(__DIR__, 3).'/overrides/Runner/Parallel/ProcessFactory.php';

beforeEach(function () {
    $this->originalMemoryLimit = ini_get('memory_limit');
});

afterEach(function () {
    ini_set('memory_limit', $this->originalMemoryLimit);
});

it('passes the parent memory limit to each worker', function () {
    ini_set('memory_limit', '1536M');

    $input = new ArrayInput([], new InputDefinition(array_map(
        fn (string $option) => new InputOption($option, null, InputOption::VALUE_OPTIONAL),
        ['diff', 'stop-on-violation', 'allow-risky', 'config', 'rules', 'using-cache', 'cache-file'],
    )));

    $commandArgs = (new ProcessFactory)->getCommandArgs(
        1234,
        ProcessIdentifier::create(),
        $input,
        new RunnerConfig(true, false, new ParallelConfig),
    );

    expect(array_slice($commandArgs, 1, 2))->toBe(['-d', ProcessUtils::escapeArgument('memory_limit=1536M')]);
});
