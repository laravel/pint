<?php

use App\Contracts\PathsRepository;
use LaravelZero\Framework\Exceptions\ConsoleException;

it('determines dirty files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('dirty')
        ->once()
        ->andReturn([
            base_path('tests/Fixtures/without-issues-laravel/file.php'),
        ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', ['--dirty' => true]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('ignores the path argument', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('dirty')
        ->once()
        ->andReturn([
            base_path('tests/Fixtures/without-issues-laravel/file.php'),
        ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--dirty' => true,
        'path' => base_path(),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('fails when git is not available', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('dirty')
        ->once()
        ->andThrow(new ConsoleException(1, 'The [--dirty] option is only available when using Git.'));

    $this->swap(PathsRepository::class, $paths);

    run('default', ['--dirty' => true]);
})->throws(ConsoleException::class, 'The [--dirty] option is only available when using Git.');

it('does not abort when there are no dirty files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('dirty')
        ->once()
        ->andReturn([]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--dirty' => true,
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 0 files');
});
