<?php

use App\Contracts\PathsRepository;
use JMac\Testing\Double;
use LaravelZero\Framework\Exceptions\ConsoleException;

it('determines diff files', function () {
    $paths = Double::for(PathsRepository::class);

    $paths->expects('diff')->with('main')->returns([
        base_path('tests/Fixtures/without-issues-laravel/file.php'),
    ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', ['--diff' => 'main']);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('ignores the path argument', function () {
    $paths = Double::for(PathsRepository::class);

    $paths->expects('diff')->returns([
        base_path('tests/Fixtures/without-issues-laravel/file.php'),
    ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--diff' => 'main',
        'path' => base_path(),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('fails when git is not available', function () {
    $paths = Double::for(PathsRepository::class);

    $paths->expects('diff')->with('main')->throws(new ConsoleException(1, 'The [--diff] option is only available when using Git.'));

    $this->swap(PathsRepository::class, $paths);

    run('default', ['--diff' => 'main']);
})->throws(ConsoleException::class, 'The [--diff] option is only available when using Git.');

it('does not abort when there are no diff files', function () {
    $paths = Double::for(PathsRepository::class);

    $paths->expects('diff')->returns([]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--diff' => 'main',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 0 files');
});

it('parses nested branch names', function () {
    $paths = Double::for(PathsRepository::class);

    $paths->expects('diff')->with('origin/main')->returns([
        base_path('tests/Fixtures/without-issues-laravel/file.php'),
    ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--diff' => 'origin/main',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});
