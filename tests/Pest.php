<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use App\Commands\DefaultCommand;
use Illuminate\Foundation\Console\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * A buffered output that also implements ConsoleOutputInterface so that
 * code writing to getErrorOutput() can be captured in tests.
 */
class TestConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    private BufferedOutput $errorBuffer;

    public function __construct()
    {
        parent::__construct(BufferedOutput::VERBOSITY_VERBOSE);
        $this->errorBuffer = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);
    }

    public function setVerbosity(int $level): void
    {
        parent::setVerbosity($level);
        $this->errorBuffer->setVerbosity($level);
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorBuffer;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->errorBuffer = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        $sections = [];

        return new ConsoleSectionOutput($this->getStream() ?: fopen('php://memory', 'rw+'), $sections, $this->getVerbosity(), $this->isDecorated(), $this->getFormatter());
    }

    public function fetchError(): string
    {
        return preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $this->errorBuffer->fetch()) ?? '';
    }
}

/*
|--------------------------------------------------------------------------
| Agent Detection
|--------------------------------------------------------------------------
|
| Pint switches to the "agent" output format when it detects that it is being
| run by an AI agent. When the suite itself runs inside one of those agents
| that detection would change Pint's output and break assertions, so we clear
| every known agent environment variable before each test to keep the suite
| deterministic regardless of where it runs. Tests that exercise agent mode
| opt back in by setting the relevant variable themselves.
|
*/

uses(TestCase::class)
    ->beforeEach(function () {
        foreach ([
            'AI_AGENT',
            'CLAUDE_CODE_IS_COWORK',
            'CURSOR_AGENT',
            'GEMINI_CLI',
            'CODEX_SANDBOX',
            'CODEX_CI',
            'CODEX_THREAD_ID',
            'AUGMENT_AGENT',
            'OPENCODE_CLIENT',
            'OPENCODE',
            'AMP_CURRENT_THREAD_ID',
            'CLAUDECODE',
            'CLAUDE_CODE',
            'REPL_ID',
            'COPILOT_MODEL',
            'COPILOT_ALLOW_ALL',
            'COPILOT_GITHUB_TOKEN',
            'COPILOT_CLI',
            'ANTIGRAVITY_AGENT',
            'PI_CODING_AGENT',
            'KIRO_AGENT_PATH',
        ] as $variable) {
            putenv($variable);
        }
    })
    ->in('Feature');

uses()
    ->beforeEach(function () {
        static $nodeIsAvailable = null;

        if ($nodeIsAvailable === null) {
            $node = new Process(['node', '--version']);
            $node->run();

            $nodeIsAvailable = $node->isSuccessful();
        }

        if (! $nodeIsAvailable) {
            $this->markTestSkipped('Node is required to run the blade formatter.');
        }
    })
    ->in('Feature/Blade');

function testOutputFile(): string
{
    $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pint-test-'.bin2hex(random_bytes(8));

    register_shutdown_function(function () use ($file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    });

    return $file;
}

function run($command, $arguments)
{
    $arguments = array_merge([
        '--test' => true,
    ], $arguments);

    if (isset($arguments['path'])) {
        $arguments['--config'] = $arguments['path'].'/pint.json';
        $arguments['path'] = [$arguments['path']];
    }

    $commandInstance = match ($command) {
        'default' => resolve(DefaultCommand::class),
    };

    // Strip global Symfony options — the command definition in tests excludes them because Application::mergeApplicationDefinition() is not called in the test path.
    $inputArguments = array_diff_key($arguments, array_flip(['--quiet', '-q']));

    $input = new ArrayInput($inputArguments, $commandInstance->getDefinition());
    $output = new TestConsoleOutput;

    app()->singleton(InputInterface::class, fn () => $input);
    app()->singleton(OutputInterface::class, fn () => $output);

    $statusCode = resolve(Kernel::class)->call($command, $arguments, $output);

    $stdout = preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $output->fetch()) ?? '';
    $stderr = $output->fetchError();

    return [$statusCode, $stdout, $stderr];
}

function bladeFixtureRoot(): string
{
    return __DIR__.'/Fixtures/blade-formatting';
}

function bladeFixtureGroupRoot(string $group): string
{
    return bladeFixtureRoot().'/'.$group;
}

function bladeFixtureGroupFiles(string $group): array
{
    return bladeFixtureFilesIn(bladeFixtureGroupRoot($group));
}

/**
 * Every blade fixture file across all groups, as paths relative to the
 * fixture root, optionally excluding the (skipped) ignorable fixtures.
 *
 * @return array<int, string>
 */
function bladeFixtureFiles(bool $includeIgnorables = true): array
{
    $files = bladeFixtureFilesIn(bladeFixtureRoot());

    if (! $includeIgnorables) {
        $files = array_values(array_filter(
            $files,
            fn (string $file): bool => ! str_starts_with($file, 'ignorables/'),
        ));
    }

    return $files;
}

/**
 * Recursively collect the ".blade.php" fixture files (ignoring the golden
 * ".expected" files) under a directory, as paths relative to it.
 *
 * @return array<int, string>
 */
function bladeFixtureFilesIn(string $root): array
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    $files = [];

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $files[] = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
        }
    }

    sort($files);

    return $files;
}

/**
 * Run "pint --blade" once over a staged temporary project, asserting it exits
 * cleanly. The caller is responsible for staging the files beforehand.
 */
function runPintBlade(string $tmp, bool $parallel = false): void
{
    file_put_contents($tmp.'/pint.json', '{"preset":"laravel"}'."\n");

    $command = ['php', 'pint', '--blade'];

    if ($parallel) {
        $command[] = '--parallel';
    }

    $command = array_merge($command, ['--config', $tmp.'/pint.json', $tmp]);

    $process = new Process($command, base_path());

    $process->setTimeout(120);
    $process->run();

    expect($process->getExitCode())->toBe(
        0,
        'pint --blade failed: '.$process->getErrorOutput().$process->getOutput(),
    );
}

/**
 * Create a fresh temporary project directory that is removed on shutdown.
 */
function freshBladeTempDirectory(): string
{
    $tmp = sys_get_temp_dir().'/pint-blade-'.bin2hex(random_bytes(6));

    @mkdir($tmp, 0777, true);

    register_shutdown_function(fn () => removeBladeTempDirectory($tmp));

    return $tmp;
}

/**
 * Stage one fixture (its input, or its golden output) into a temporary project.
 * The file is read from "$sourceRoot/$relative" and written, under an optional
 * "$targetPrefix" subdirectory, at the same relative path inside "$tmp".
 */
function stageBladeFixture(string $tmp, string $sourceRoot, string $relative, bool $fromExpected = false, string $targetPrefix = ''): void
{
    $target = $tmp.'/'.$targetPrefix.$relative;
    @mkdir(dirname($target), 0777, true);

    $source = $sourceRoot.'/'.$relative.($fromExpected ? '.expected' : '');
    file_put_contents($target, file_get_contents($source));
}

/**
 * Stage and format a fixture group's fixtures, caching the temporary directory
 * so every dataset case in the group's test file asserts against a single run.
 *
 * For a regular group the inputs are staged under "input/" and the golden
 * files under "golden/" inside one project, so a *single* Pint invocation can
 * cover both the golden-file check (does "input" format to "golden"?) and the
 * idempotency check (does "golden" survive a re-format?). Halving the number
 * of "node" worker boots this way is what keeps the parallel suite fast.
 *
 * The "ignorables" group is special: those fixtures must be skipped by Pint,
 * which relies on their real relative paths (e.g. "node_modules/...",
 * "resources/views/emails/..."). They are therefore staged at the project root
 * with no "input/" prefix, and formatting them is expected to be a no-op.
 */
function formatBladeFixtureGroup(string $group): string
{
    static $cache = [];

    if (isset($cache[$group])) {
        return $cache[$group];
    }

    $sourceRoot = bladeFixtureGroupRoot($group);
    $tmp = freshBladeTempDirectory();

    foreach (bladeFixtureGroupFiles($group) as $relative) {
        if ($group === 'ignorables') {
            stageBladeFixture($tmp, $sourceRoot, $relative);

            continue;
        }

        stageBladeFixture($tmp, $sourceRoot, $relative, targetPrefix: 'input/');
        stageBladeFixture($tmp, $sourceRoot, $relative, fromExpected: true, targetPrefix: 'golden/');
    }

    runPintBlade($tmp);

    return $cache[$group] = $tmp;
}

/**
 * One representative fixture per non-ignorable group, used to exercise Pint's
 * "--parallel" worker pool without re-formatting every fixture (the per-group
 * files already cover correctness; parallelism does not change per-file output).
 *
 * @return array<int, string>
 */
function bladeParallelSample(): array
{
    $samples = [];

    foreach (array_map('basename', glob(bladeFixtureRoot().'/*', GLOB_ONLYDIR)) as $group) {
        if ($group === 'ignorables') {
            continue;
        }

        if (($files = bladeFixtureGroupFiles($group)) !== []) {
            $samples[] = $group.'/'.$files[0];
        }
    }

    sort($samples);

    return $samples;
}

/**
 * Stage and format the parallel sample once with "--parallel", caching the
 * result so the parallel test file can assert per fixture.
 */
function formatBladeFixturesInParallel(): string
{
    static $tmp = null;

    if ($tmp === null) {
        $tmp = freshBladeTempDirectory();

        foreach (bladeParallelSample() as $relative) {
            stageBladeFixture($tmp, bladeFixtureRoot(), $relative);
        }

        runPintBlade($tmp, parallel: true);
    }

    return $tmp;
}

/**
 * Register the golden-file and idempotency tests for a blade fixture group.
 * Keeping this as a one-line call per group file keeps every group in its own
 * test file (so the parallel runner can pick them up) without duplicating body.
 */
function bladeFixtureTest(string $group): void
{
    if ($group === 'ignorables') {
        it('leaves every ignorable fixture untouched', function (string $file) use ($group) {
            $tmp = formatBladeFixtureGroup($group);

            expect(file_get_contents($tmp.'/'.$file))->toBe(
                file_get_contents(bladeFixtureGroupRoot($group).'/'.$file.'.expected'),
                "Ignorable fixture [{$group}/{$file}] was modified but should have been skipped.",
            );
        })->with(fn () => bladeFixtureGroupFiles($group));

        return;
    }

    it('formats every fixture to its golden file', function (string $file) use ($group) {
        $tmp = formatBladeFixtureGroup($group);

        expect(file_get_contents($tmp.'/input/'.$file))->toBe(
            file_get_contents(bladeFixtureGroupRoot($group).'/'.$file.'.expected'),
            "Formatted output does not match the golden file for [{$group}/{$file}].",
        );
    })->with(fn () => bladeFixtureGroupFiles($group));

    it('re-formats every golden file unchanged (idempotent)', function (string $file) use ($group) {
        $tmp = formatBladeFixtureGroup($group);

        expect(file_get_contents($tmp.'/golden/'.$file))->toBe(
            file_get_contents(bladeFixtureGroupRoot($group).'/'.$file.'.expected'),
            "Re-formatting the golden file changed it for [{$group}/{$file}] (not idempotent).",
        );
    })->with(fn () => bladeFixtureGroupFiles($group));
}

/**
 * Recursively remove a staged temporary blade project.
 */
function removeBladeTempDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $file) {
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }

    @rmdir($directory);
}
