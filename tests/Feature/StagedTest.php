<?php

use App\Contracts\PathsRepository;

it('determines staged files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('staged')
        ->once()
        ->andReturn([
            base_path('tests/Fixtures/without-issues-laravel/file.php'),
        ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', ['--staged' => true]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('ignores the path argument', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('staged')
        ->once()
        ->andReturn([
            base_path('tests/Fixtures/without-issues-laravel/file.php'),
        ]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '-s' => true,
        'path' => base_path(),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 1 file');
});

it('does not abort when there are no staged files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('staged')
        ->once()
        ->andReturn([]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--staged' => true,
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 0 files');
});
