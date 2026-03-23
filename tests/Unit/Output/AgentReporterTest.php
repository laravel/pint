<?php

use App\Output\AgentReporter;
use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Error\Error;
use PhpCsFixer\Error\ErrorsManager;

it('returns pass when no changes and no errors', function () {
    $reporter = new AgentReporter;
    $summary = new ReportSummary([], 10, 0, 0, false, false, false);

    $output = $reporter->generate($summary);
    $json = json_decode($output, true);

    expect($json['result'])->toBe('pass')
        ->and($json)->not->toHaveKey('files')
        ->and($json)->not->toHaveKey('errors');
});

it('returns fail when changes exist in dry-run mode', function () {
    $reporter = new AgentReporter;
    $summary = new ReportSummary([
        '/project/app/Example.php' => [
            'appliedFixers' => ['binary_operator_spaces'],
            'diff' => '',
        ],
    ], 10, 0, 0, false, true, false);

    $output = $reporter->generate($summary);
    $json = json_decode($output, true);

    expect($json['result'])->toBe('fail')
        ->and($json)->toHaveKey('files');
});

it('returns fixed when changes exist in fix mode', function () {
    $reporter = new AgentReporter;
    $summary = new ReportSummary([
        '/project/app/Example.php' => [
            'appliedFixers' => ['binary_operator_spaces'],
            'diff' => '',
        ],
    ], 10, 0, 0, false, false, false);

    $output = $reporter->generate($summary);
    $json = json_decode($output, true);

    expect($json['result'])->toBe('fixed')
        ->and($json)->toHaveKey('files');
});

it('returns fail when errors exist', function () {
    $errorsManager = new ErrorsManager;
    $errorsManager->report(
        new Error(Error::TYPE_LINT, 'app/Example.php', new Exception('Parse error'))
    );

    $reporter = new AgentReporter($errorsManager);
    $summary = new ReportSummary([], 10, 0, 0, false, false, false);

    $output = $reporter->generate($summary);
    $json = json_decode($output, true);

    expect($json['result'])->toBe('fail')
        ->and($json)->toHaveKey('errors')
        ->and($json['errors'][0]['message'])->toBe('Parse error');
});

it('includes files with path and fixers', function () {
    $reporter = new AgentReporter;
    $summary = new ReportSummary([
        getcwd().'/app/Example.php' => [
            'appliedFixers' => ['binary_operator_spaces', 'no_unused_imports'],
            'diff' => '',
        ],
    ], 10, 0, 0, false, false, false);

    $output = $reporter->generate($summary);
    $json = json_decode($output, true);

    expect($json['files'][0]['path'])->toEndWith('Example.php')
        ->and($json['files'][0]['fixers'])->toBe(['binary_operator_spaces', 'no_unused_imports']);
});

it('uses relative paths', function () {
    $cwd = getcwd();
    $reporter = new AgentReporter;
    $summary = new ReportSummary([
        $cwd.'/app/Models/User.php' => [
            'appliedFixers' => ['single_quote'],
            'diff' => '',
        ],
        $cwd.'/app/Http/Controllers/HomeController.php' => [
            'appliedFixers' => ['trailing_comma_in_multiline'],
            'diff' => '',
        ],
    ], 10, 0, 0, false, false, false);

    $output = $reporter->generate($summary);
    $json = json_decode($output, true);

    expect($json['files'][0]['path'])->toEndWith('User.php')
        ->and($json['files'][1]['path'])->toEndWith('HomeController.php');
});

it('outputs valid json', function () {
    $reporter = new AgentReporter;
    $summary = new ReportSummary([
        getcwd().'/app/Example.php' => [
            'appliedFixers' => ['binary_operator_spaces'],
            'diff' => '',
        ],
    ], 10, 0, 0, false, false, false);

    $output = $reporter->generate($summary);

    expect($output)->toBeJson();
});

it('returns format name as agent', function () {
    $reporter = new AgentReporter;

    expect($reporter->getFormat())->toBe('agent');
});
