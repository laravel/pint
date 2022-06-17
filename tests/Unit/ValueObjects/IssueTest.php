<?php

use App\ValueObjects\Issue;

it('has a description', function () {
    $issue = new Issue(__DIR__, __FILE__, 'F', ['appliedFixers' => 'rule_a']);

    $description = $issue->description(false);

    expect($description)->toContain('rule_a');
});

it('has a file', function () {
    $issue = new Issue(__DIR__, __FILE__, 'F', ['appliedFixers' => 'rule_a']);

    $file = $issue->file();

    expect($file)->toBe(basename(__FILE__));
});

it('has a symbol', function () {
    $issue = new Issue(__DIR__, __FILE__, 'F', ['appliedFixers' => 'rule_a']);

    $symbol = $issue->symbol();

    expect($symbol)->toBe('F');
});

it('may be an error', function () {
    $nonAnError = new Issue(__DIR__, __FILE__, 'F', ['appliedFixers' => 'rule_a']);
    $error = new Issue(__DIR__, __FILE__, 'F', []);

    expect($nonAnError->isError())->toBeFalse();
    expect($error->isError())->toBeTrue();
});
