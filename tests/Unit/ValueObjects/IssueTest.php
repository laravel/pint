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

it('may be fixable or not', function () {
    $fixable = new Issue(__DIR__, __FILE__, 'F', ['appliedFixers' => 'rule_a']);
    $nonFixable = new Issue(__DIR__, __FILE__, 'F', []);

    expect($fixable->fixable())->toBeTrue()
        ->and($nonFixable->fixable())->toBeFalse();
});
