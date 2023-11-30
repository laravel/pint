<?php

use App\Actions\ElaborateSummary;
use App\Actions\FixCode;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Output\BufferedOutput;

use function Termwind\renderUsing;

beforeEach(function () {
    Process::preventStrayProcesses();
});

it('handles clean working tree', function () {
    [$statusCode, $output] = run('default', [
        '--commit' => true,
        'path' => base_path('tests/Fixtures/without-issues'),
    ]);

    expect($statusCode)->toBe(0)
        ->and($output)
        ->toContain('Nothing to commit, working tree clean.');
});

it('prints process error', function () {
    // For some reason, the output is not captured by the buffer when a non-zero exit code is returned, so we have to check the renderer directly.
    renderUsing($renderer = new BufferedOutput());

    $basePath = base_path('tests/Fixtures/with-fixable-issues/file.php');

    $fixerOutput = [
        $basePath => [
            'appliedFixers' => ['psr12'],
            'diff' => '',
        ],
    ];

    $this->swap(FixCode::class, tap(Mockery::mock(FixCode::class), fn ($fixer) => $fixer
        ->shouldReceive('execute')
        ->once()
        ->andReturn([1, $fixerOutput])
    ));

    $this->swap(ElaborateSummary::class, tap(Mockery::mock(ElaborateSummary::class), fn ($summary) => $summary
        ->shouldReceive('execute')
        ->once()
        ->with(1, $fixerOutput)
        ->andReturn(0)
    ));

    Process::shouldReceive('run')
        ->once()
        ->with(sprintf('git commit -m "Apply style fixes from Laravel Pint" %s', $basePath))
        ->andReturn(tap(Mockery::mock(FakeProcessResult::class), fn ($process) => $process
            ->shouldReceive('failed')
            ->once()
            ->andReturn(true)
            ->shouldReceive('errorOutput')
            ->once()
            ->andReturn('Example Git error')
        ));

    [$statusCode, $output] = run('default', [
        '--commit' => true,
        'path' => $basePath,
    ]);

    expect($statusCode)->toBe(1)
        ->and($renderer->fetch())
        ->toBe('    ERROR:   Example Git error  '.PHP_EOL)
        ->and($output)
        ->not()->toContain('Apply style fixes from Laravel Pint');
});

it('commits the changes', function () {
    renderUsing($renderer = new BufferedOutput());

    $basePath = base_path('tests/Fixtures/with-fixable-issues/file.php');

    $fixerOutput = [
        $basePath => [
            'appliedFixers' => ['psr12'],
            'diff' => '',
        ],
    ];

    $this->swap(FixCode::class, tap(Mockery::mock(FixCode::class), fn ($fixer) => $fixer
        ->shouldReceive('execute')
        ->once()
        ->andReturn([1, $fixerOutput])
    ));

    $this->swap(ElaborateSummary::class, tap(Mockery::mock(ElaborateSummary::class), fn ($summary) => $summary
        ->shouldReceive('execute')
        ->once()
        ->with(1, $fixerOutput)
        ->andReturn(0)
    ));

    Process::shouldReceive('run')
        ->once()
        ->with('git commit -m "Apply style fixes from Laravel Pint" '.base_path('tests/Fixtures/with-fixable-issues/file.php'))
        ->andReturn(new FakeProcessResult());

    [$statusCode, $output] = run('default', [
        '--commit' => true,
        'path' => base_path('tests/Fixtures/with-fixable-issues/file.php'),
    ]);

    expect($statusCode)->toBe(0)
        ->and($renderer->fetch())
        ->toBe('  Changes committed successfully!  '.PHP_EOL);
});
