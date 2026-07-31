<?php

use Symfony\Component\Process\Process;

it('reads and updates a configured cache file and skips unchanged files', function () {
    $directory = sys_get_temp_dir().'/pint-cache-'.bin2hex(random_bytes(6));
    $cache = $directory.'/cache.json';
    $config = $directory.'/pint.json';
    $source = $directory.'/Example.php';

    mkdir($directory, 0777, true);

    file_put_contents($config, json_encode([
        'preset' => 'laravel',
        'cache-file' => $cache,
    ]));
    file_put_contents($source, "<?php\n\nfunction cachedExample(): void\n{\n    // Cached.\n}\n");

    $run = function () use ($config, $source): Process {
        $process = new Process([PHP_BINARY, base_path('pint'), '--config', $config, $source], base_path());
        $process->setTimeout(30);
        $process->run();

        expect($process->getExitCode())->toBe(0, $process->getErrorOutput().$process->getOutput());

        return $process;
    };

    try {
        $run();

        expect($cache)->toBeFile();

        $firstCache = file_get_contents($cache);
        $cacheData = json_decode($firstCache, true, flags: JSON_THROW_ON_ERROR);

        expect($cacheData['hashes'])->toHaveCount(1);

        touch($cache, 946684800);
        clearstatcache(true, $cache);

        $run();

        clearstatcache(true, $cache);

        expect(filemtime($cache))->toBe(946684800)
            ->and(file_get_contents($cache))->toBe($firstCache);

        file_put_contents($source, "<?php\n\nfunction cachedExample(): void\n{\n    // Changed.\n}\n");

        $run();

        clearstatcache(true, $cache);

        expect(filemtime($cache))->toBeGreaterThan(946684800)
            ->and(file_get_contents($cache))->not->toBe($firstCache);
    } finally {
        @unlink($source);
        @unlink($config);
        @unlink($cache);
        @rmdir($directory);
    }
});
