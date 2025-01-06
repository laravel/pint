<?php

use App\Contracts\PathsRepository;

it('determines diff files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('diff')
        ->with('main')
        ->once()
        ->andReturn([
            base_path('tests/Fixtures/without-issues-laravel/file.php'),
        ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', ['--diff' => 'main']);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('ignores the path argument', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('diff')
        ->once()
        ->andReturn([
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

it('does not abort when there are no diff files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('diff')
        ->once()
        ->andReturn([]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--diff' => 'main',
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 0 files');
});

it('parses nested branch names', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('diff')
        ->with('origin/main')
        ->once()
        ->andReturn([
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
