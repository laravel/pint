<?php

use App\BladeFormatter;
use App\Factories\ConfigurationFactory;
use App\Repositories\ConfigurationJsonRepository;

it('enables caching when blade formatting is disabled', function () {
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository(null, null));
    app()->bind(BladeFormatter::class, fn () => Mockery::mock(BladeFormatter::class));

    expect(ConfigurationFactory::preset([])->getUsingCache())->toBeTrue();
});

it('disables caching when blade formatting is enabled', function () {
    $configPath = tempnam(sys_get_temp_dir(), 'pint-config-');
    file_put_contents($configPath, json_encode([
        'rules' => ['Pint/laravel_blade' => true],
    ]));

    try {
        app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository($configPath, null));
        app()->bind(BladeFormatter::class, fn () => Mockery::mock(BladeFormatter::class));

        expect(ConfigurationFactory::preset([])->getUsingCache())->toBeFalse();
    } finally {
        @unlink($configPath);
    }
});

it('returns false for non-excluded files', function () {
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository(null, null));

    expect(ConfigurationFactory::isPathExcluded('src/MyClass.php'))->toBeFalse()
        ->and(ConfigurationFactory::isPathExcluded('app/Services/UserService.php'))->toBeFalse()
        ->and(ConfigurationFactory::isPathExcluded('tests/Unit/MyTest.php'))->toBeFalse();
});

it('excludes files matching default notName patterns', function () {
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository(null, null));

    expect(ConfigurationFactory::isPathExcluded('resources/views/welcome.blade.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('app/User.blade.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('_ide_helper.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('_ide_helper_models.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('.phpstorm.meta.php'))->toBeTrue();
});

it('excludes files in default exclude folders', function () {
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository(null, null));

    expect(ConfigurationFactory::isPathExcluded('node_modules/package/index.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('storage/logs/app.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('build/output.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('bootstrap/cache/services.php'))->toBeTrue();
});

it('excludes files based on pint.json exclude config', function () {
    $configPath = dirname(__DIR__, 2).'/Fixtures/finder/pint.json';
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository($configPath, null));

    expect(ConfigurationFactory::isPathExcluded('my-dir/SomeFile.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('my-dir/nested/File.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('other-dir/File.php'))->toBeFalse();
});

it('excludes files matching pint.json notName patterns', function () {
    $configPath = dirname(__DIR__, 2).'/Fixtures/finder/pint.json';
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository($configPath, null));

    expect(ConfigurationFactory::isPathExcluded('src/test-my-file.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('app/Models/foo-my-file.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('test-other-file.php'))->toBeFalse();
});

it('excludes files matching pint.json notPath patterns', function () {
    $configPath = dirname(__DIR__, 2).'/Fixtures/finder/pint.json';
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository($configPath, null));

    expect(ConfigurationFactory::isPathExcluded('path/to/excluded-file.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('path/to/other-file.php'))->toBeFalse();
});

it('handles absolute paths correctly', function () {
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository(null, null));

    $absolutePath = getcwd().DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app.php';

    expect(ConfigurationFactory::isPathExcluded($absolutePath))->toBeTrue();
});

it('handles paths with backslashes on Windows', function () {
    $configPath = dirname(__DIR__, 2).'/Fixtures/finder/pint.json';
    app()->bind(ConfigurationJsonRepository::class, fn () => new ConfigurationJsonRepository($configPath, null));

    expect(ConfigurationFactory::isPathExcluded('my-dir\\nested\\File.php'))->toBeTrue()
        ->and(ConfigurationFactory::isPathExcluded('path\\to\\excluded-file.php'))->toBeTrue();
});
