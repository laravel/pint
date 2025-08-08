<?php

use App\Services\PresetManifest;
use Illuminate\Filesystem\Filesystem;

it('can discover presets from composer packages', function () {
    $files = Mockery::mock(Filesystem::class);

    $files->shouldReceive('exists')
        ->with('/test/path/bootstrap/cache/pint_presets.php')
        ->andReturn(false);

    $files->shouldReceive('exists')
        ->with('/test/path/vendor/composer/installed.json')
        ->andReturn(true);

    $files->shouldReceive('lastModified')
        ->with('/test/path/vendor/composer/installed.json')
        ->andReturn(time());

    $files->shouldReceive('get')
        ->with('/test/path/vendor/composer/installed.json')
        ->andReturn(json_encode([
            'packages' => [
                [
                    'name' => 'acme/pint-presets',
                    'extra' => [
                        'laravel-pint' => [
                            'presets' => [
                                'acme' => 'src/presets/acme.php',
                                'acme-strict' => 'src/presets/strict.php',
                            ],
                        ],
                    ],
                ],
            ],
        ]));

    $files->shouldReceive('exists')
        ->with('/test/path/composer.json')
        ->andReturn(false);

    $files->shouldReceive('exists')
        ->with('/test/path/vendor/acme/pint-presets/src/presets/acme.php')
        ->andReturn(true);

    $files->shouldReceive('exists')
        ->with('/test/path/vendor/acme/pint-presets/src/presets/strict.php')
        ->andReturn(true);

    $files->shouldReceive('ensureDirectoryExists')
        ->with('/test/path/bootstrap/cache', 0755, true);

    $files->shouldReceive('replace')
        ->with('/test/path/bootstrap/cache/pint_presets.php', Mockery::type('string'));

    $manifest = new PresetManifest(
        $files,
        '/test/path',
        '/test/path/bootstrap/cache/pint_presets.php',
    );

    expect($manifest->has('acme'))->toBeTrue();
    expect($manifest->has('acme-strict'))->toBeTrue();
    expect($manifest->path('acme'))->toBe('/test/path/vendor/acme/pint-presets/src/presets/acme.php');
});

it('handles missing composer installed.json gracefully', function () {
    $files = Mockery::mock(Filesystem::class);

    $files->shouldReceive('exists')
        ->with('/test/path/bootstrap/cache/pint_presets.php')
        ->andReturn(false);

    $files->shouldReceive('exists')
        ->with('/test/path/vendor/composer/installed.json')
        ->andReturn(false);

    $files->shouldReceive('exists')
        ->with('/test/path/composer.json')
        ->andReturn(false);

    $files->shouldReceive('ensureDirectoryExists')
        ->with('/test/path/bootstrap/cache', 0755, true);

    $files->shouldReceive('replace')
        ->with('/test/path/bootstrap/cache/pint_presets.php', "<?php return array (\n);");

    $manifest = new PresetManifest(
        $files,
        '/test/path',
        '/test/path/bootstrap/cache/pint_presets.php',
    );

    expect($manifest->names())->toBeEmpty();
});
