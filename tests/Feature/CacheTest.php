<?php

use App\Fixers\PrettierCacheFingerprint;
use Symfony\Component\Process\Process;

/**
 * Run pint over the given directory using the given cache file, returning its exit code.
 *
 * @param  array<int, string>  $options
 */
function runPintWithCache(string $directory, string $cacheFile, array $options = []): int
{
    $command = array_merge(
        ['php', 'pint', '--config', $directory.'/pint.json', '--cache-file', $cacheFile],
        $options,
        [$directory],
    );

    $process = new Process($command, base_path());
    $process->setTimeout(120);
    $process->run();

    return $process->getExitCode();
}

/**
 * @return array<string, mixed>
 */
function cacheSignature(string $cacheFile): array
{
    expect($cacheFile)->toBeFile();

    return json_decode((string) file_get_contents($cacheFile), true, flags: JSON_THROW_ON_ERROR);
}

beforeEach(function () {
    $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pint-cache-'.bin2hex(random_bytes(8));
    $this->cacheFile = $this->directory.DIRECTORY_SEPARATOR.'.pint.cache';

    mkdir($this->directory.'/resources/views', 0777, true);

    file_put_contents($this->directory.'/pint.json', '{"preset":"laravel"}'."\n");
    file_put_contents($this->directory.'/resources/views/welcome.blade.php', "<div   class=\"p-4 flex\"><x-foo    :bar=\"\$baz\"/></div>\n");
    file_put_contents($this->directory.'/Example.php', "<?php\n\nclass Example\n{\n    public function handle()\n    {\n        return array(1, 2);\n    }\n}\n");
});

afterEach(function () {
    foreach (['/resources/views/welcome.blade.php', '/Example.php', '/pint.json', '/.pint.cache'] as $file) {
        @unlink($this->directory.$file);
    }

    @rmdir($this->directory.'/resources/views');
    @rmdir($this->directory.'/resources');
    @rmdir($this->directory);
});

it('uses the cache when blade formatting is active', function () {
    expect(runPintWithCache($this->directory, $this->cacheFile, ['--blade']))->toBe(0);

    $signature = cacheSignature($this->cacheFile);

    $cached = array_map('basename', array_keys($signature['hashes']));

    expect($signature['rules'])->toHaveKey('Pint/laravel_blade')
        ->and($cached)->toContain('welcome.blade.php')
        ->and($cached)->toContain('Example.php');
});

it('carries the prettier fingerprints in the cache signature', function () {
    expect(runPintWithCache($this->directory, $this->cacheFile, ['--blade']))->toBe(0);

    $rules = cacheSignature($this->cacheFile)['rules'];
    $fingerprints = $rules[(new PrettierCacheFingerprint)->getName()]['fingerprints'] ?? null;

    expect($fingerprints)->toHaveKey('Pint/laravel_blade')
        ->and($fingerprints['Pint/laravel_blade'])->toMatch('/^[a-f0-9]{32}$/');
});

it('does not carry prettier fingerprints when blade formatting is inactive', function () {
    expect(runPintWithCache($this->directory, $this->cacheFile))->toBe(0);

    $rules = cacheSignature($this->cacheFile)['rules'];

    expect($rules)->not->toHaveKey((new PrettierCacheFingerprint)->getName())
        ->and($rules)->not->toHaveKey('Pint/laravel_blade');
});

it('reports no changes on a second run served from the cache', function () {
    expect(runPintWithCache($this->directory, $this->cacheFile, ['--blade']))->toBe(0);

    $signature = cacheSignature($this->cacheFile);

    expect(runPintWithCache($this->directory, $this->cacheFile, ['--blade', '--test']))->toBe(0)
        ->and(cacheSignature($this->cacheFile))->toBe($signature);
});
