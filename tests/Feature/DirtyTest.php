<?php

use App\Contracts\PathsRepository;

it('determines dirty files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('dirty')
        ->once()
        ->andReturn([
            base_path('tests/Fixtures/without-issues/file.php'),
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
            base_path('tests/Fixtures/without-issues/file.php'),
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

it('aborts when there are no dirty files', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('dirty')
        ->once()
        ->andReturn([]);

    $this->swap(PathsRepository::class, $paths);

    run('default', ['--dirty' => true]);
})->throws(Exception::class, 'No dirty files found.');

it('does not abort there are no dirty files and this is ignored', function () {
    $paths = Mockery::mock(PathsRepository::class);

    $paths
        ->shouldReceive('dirty')
        ->once()
        ->andReturn([]);

    $this->swap(PathsRepository::class, $paths);

    [$statusCode, $output] = run('default', [
        '--dirty' => true,
        '--ignore-no-changes' => true,
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('── Laravel', ' 0 files');
});
