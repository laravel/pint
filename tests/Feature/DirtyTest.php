<?php

it('uses Git to determine dirty files', function () {
    \Facades\App\Support\Git::expects('dirtyFiles')
        ->andReturn([[base_path('tests/Fixtures/without-issues/file.php')], true]);

    [$statusCode, $output] = run('default', ['--dirty' => true]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('ignores the path argument', function () {
    \Facades\App\Support\Git::expects('dirtyFiles')
        ->andReturn([[base_path('tests/Fixtures/without-issues/file.php')], true]);

    [$statusCode, $output] = run('default', [
        '--dirty' => true,
        'path' => base_path(),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('fails when not successful', function () {
    \Facades\App\Support\Git::expects('dirtyFiles')
        ->andReturn([[], false]);

    run('default', ['--dirty' => true]);
})->throws(Exception::class, 'Option [dirty] must be used within a Git repository.');

it('aborts when there are no dirty files', function () {
    \Facades\App\Support\Git::expects('dirtyFiles')
        ->andReturn([[], true]);

    run('default', ['--dirty' => true]);
})->throws(Exception::class, 'No dirty files found.');
