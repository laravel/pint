<?php

use Illuminate\Filesystem\Filesystem;
use LaravelZero\Framework\Exceptions\ConsoleException;

/**
 * Run pint in test mode with the given configuration written to the given file of the project.
 *
 * @param  array<string, mixed>  $configuration
 * @return array{0: int, 1: string}
 */
function runPintWithConfiguration(string $project, array $configuration, string $path = 'pint.json'): array
{
    file_put_contents($project.'/'.$path, json_encode($configuration));

    return run('default', ['--test' => true, '--config' => $path]);
}

beforeEach(function () {
    $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pint-paths-'.bin2hex(random_bytes(8));
    $this->project = $this->root.DIRECTORY_SEPARATOR.'project';
    $this->cwd = getcwd();

    mkdir($this->project.'/package', 0777, true);
    mkdir($this->project.'/vendor');
    mkdir($this->root.'/outside');

    file_put_contents($this->root.'/outside/base.json', '{"preset":"psr12"}');
    file_put_contents($this->project.'/base.json', '{"preset":"psr12"}');
    file_put_contents($this->project.'/vendor/autoload.php', "<?php\n\nreturn require __DIR__.'/composer/autoload_real.php';\n");
    file_put_contents($this->project.'/Example.php', "<?php\n\$a  =  1;\n");

    symlink($this->root.'/outside', $this->project.'/linked');
    symlink($this->root.'/outside/missing.php', $this->project.'/dangling.php');

    chdir($this->project);
});

afterEach(function () {
    chdir($this->cwd);

    (new Filesystem)->deleteDirectory($this->root);
});

it('rejects a cache file outside the project', function (string $cacheFile, string $written) {
    $cacheFile = str_replace('{root}', $this->root, $cacheFile);

    expect(fn () => runPintWithConfiguration($this->project, ['cache-file' => $cacheFile]))
        ->toThrow(ConsoleException::class, 'The [cache-file] path ['.$cacheFile.'] must be inside');

    expect($this->root.$written)->not->toBeFile();
})->with([
    'relative' => ['../victim.php', '/victim.php'],
    'absolute' => ['{root}/victim.php', '/victim.php'],
    'symlinked directory' => ['linked/victim.php', '/outside/victim.php'],
    'parent of a symlinked directory' => ['linked/../victim.php', '/victim.php'],
    'parent of a missing directory' => ['missing/../../victim.php', '/victim.php'],
    'dangling symlink' => ['dangling.php', '/outside/missing.php'],
]);

it('does not overwrite a project file that is not a cache', function () {
    $autoloader = file_get_contents($this->project.'/vendor/autoload.php');

    expect(fn () => runPintWithConfiguration($this->project, ['cache-file' => 'vendor/autoload.php']))
        ->toThrow(ConsoleException::class, 'already exists and is not a Pint cache.');

    expect(file_get_contents($this->project.'/vendor/autoload.php'))->toBe($autoloader);
});

it('writes a cache file inside the project', function (string $cacheFile, bool $empty) {
    if ($empty) {
        touch($this->project.'/'.$cacheFile);
    }

    [$statusCode] = runPintWithConfiguration($this->project, ['cache-file' => $cacheFile]);

    expect($statusCode)->toBe(1)
        ->and(json_decode((string) file_get_contents($this->project.'/'.$cacheFile), true))->toHaveKey('hashes');
})->with([
    'new file' => ['.pint.cache', false],
    'new folders' => ['build/cache/.pint.cache', false],
    'empty file' => ['package/.pint.cache', true],
]);

it('extends a file anywhere inside the project', function () {
    [$statusCode] = runPintWithConfiguration($this->project, ['extend' => '../base.json'], 'package/pint.json');

    expect($statusCode)->toBe(1);
});

it('rejects extending a file outside the project', function () {
    runPintWithConfiguration($this->project, ['extend' => '../outside/base.json']);
})->throws(ConsoleException::class, 'The [extend] path');

it('trusts a configuration file outside the project', function () {
    file_put_contents($this->root.'/outside/pint.json', json_encode([
        'extend' => 'base.json',
        'cache-file' => $this->root.'/pint.cache',
    ]));

    [$statusCode] = run('default', ['--test' => true, '--config' => $this->root.'/outside/pint.json']);

    expect($statusCode)->toBe(1)
        ->and($this->root.'/pint.cache')->toBeFile();
});
