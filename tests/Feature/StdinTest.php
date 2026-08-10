<?php

use Illuminate\Support\Facades\Process;

it('formats code from stdin', function (string $input, ?string $expected) {
    $result = Process::input($input)
        ->run('php pint - --stdin-filename=app/Test.php')
        ->throw();

    expect($result)
        ->output()
        ->toBe($expected ?? $input)
        ->errorOutput()
        ->toBe('');
})->with([
    'basic array and conditional' => [
        <<<'PHP'
        <?php
        $array = array("a","b");
        if($condition==true){
            echo "test";
        }
        PHP
        ,
        <<<'PHP'
        <?php

        $array = ['a', 'b'];
        if ($condition == true) {
            echo 'test';
        }

        PHP
        ,
    ],
    'class with method' => [
        <<<'PHP'
        <?php
        class Test{
        public function method(){
        return array("key"=>"value");
        }
        }
        PHP
        ,
        <<<'PHP'
        <?php

        class Test
        {
            public function method()
            {
                return ['key' => 'value'];
            }
        }

        PHP
        ,
    ],
    'already formatted code' => [
        <<<'PHP'
        <?php

        class AlreadyFormatted
        {
            public function method()
            {
                return ['key' => 'value'];
            }
        }

        PHP
        ,
        null,
    ],
]);

it('formats code from stdin without filename', function () {
    $input = <<<'PHP'
    <?php
    $array = array("a","b");
    PHP;

    $expected = <<<'PHP'
    <?php

    $array = ['a', 'b'];

    PHP;

    $result = Process::input($input)->run('php pint -')->throw();

    expect($result)->output()->toBe($expected)->errorOutput()->toBe('');
});

it('uses stdin-filename for context', function () {
    $input = <<<'PHP'
    <?php
    $array = array("test");
    PHP;

    $expected = <<<'PHP'
    <?php

    $array = ['test'];

    PHP;

    $result = Process::input($input)
        ->run('php pint - --stdin-filename=app/Models/User.php')
        ->throw();

    expect($result)->output()->toBe($expected)->errorOutput()->toBe('');
});

it('formats code from stdin using only stdin-filename option', function () {
    $input = <<<'PHP'
    <?php
    $array = array("foo","bar");
    PHP;

    $expected = <<<'PHP'
    <?php

    $array = ['foo', 'bar'];

    PHP;

    $result = Process::input($input)
        ->run('php pint --stdin-filename=app/Models/Example.php')
        ->throw();

    expect($result)->output()->toBe($expected)->errorOutput()->toBe('');
});

it('skips formatting for excluded paths', function (string $filename) {
    $input = <<<'PHP'
    <?php
    $array = array("foo","bar");
    PHP;

    $result = Process::input($input)
        ->run("php pint --stdin-filename={$filename}")
        ->throw();

    expect($result)->output()->toBe($input)->errorOutput()->toBe('');
})->with([
    'blade files' => ['resources/views/welcome.blade.php'],
    'storage folder' => ['storage/framework/views/compiled.php'],
    'node_modules' => ['node_modules/package/index.php'],
]);

it('respects pint.json exclusion rules', function (string $filename, bool $shouldFormat) {
    $input = <<<'PHP'
    <?php
    $array = array("foo","bar");
    PHP;

    $expected = $shouldFormat ? <<<'PHP'
    <?php

    $array = ['foo', 'bar'];

    PHP
        : $input;

    $result = Process::input($input)
        ->path(base_path('tests/Fixtures/finder'))
        ->run('php '.base_path('pint')." --stdin-filename={$filename}")
        ->throw();

    expect($result)->output()->toBe($expected)->errorOutput()->toBe('');
})->with([
    'excluded folder' => ['my-dir/SomeFile.php', false],
    'excluded notName pattern' => ['src/test-my-file.php', false],
    'excluded notPath pattern' => ['path/to/excluded-file.php', false],
    'not excluded' => ['src/MyClass.php', true],
]);

it('gives filename derived fixers the stdin-filename', function () {
    $input = <<<'PHP'
    <?php

    namespace App\Models;

    class Wrong
    {
    }

    PHP;

    $expected = <<<'PHP'
    <?php

    namespace App\Models;

    class User {}

    PHP;

    $result = Process::input($input)
        ->path(base_path('tests/Fixtures/psr-autoloading'))
        ->run('php '.base_path('pint').' --stdin-filename=app/Models/User.php')
        ->throw();

    expect($result)->output()->toBe($expected)->errorOutput()->toBe('');
});

it('renames the class to the stdin-filename across common Laravel paths', function (string $path, string $class) {
    $input = <<<'PHP'
    <?php

    namespace App;

    class Wrong
    {
    }

    PHP;

    $result = Process::input($input)
        ->path(base_path('tests/Fixtures/psr-autoloading'))
        ->run('php '.base_path('pint').' --stdin-filename='.escapeshellarg($path))
        ->throw();

    expect($result->output())
        ->toContain("class {$class} {}")
        ->not->toContain('pint_stdin_');
})->with([
    'model' => ['app/Models/User.php', 'User'],
    'controller' => ['app/Http/Controllers/UserController.php', 'UserController'],
    'middleware' => ['app/Http/Middleware/Authenticate.php', 'Authenticate'],
    'service provider' => ['app/Providers/AppServiceProvider.php', 'AppServiceProvider'],
    'form request' => ['app/Http/Requests/StoreUserRequest.php', 'StoreUserRequest'],
    'job' => ['app/Jobs/ProcessPodcast.php', 'ProcessPodcast'],
    'feature test' => ['tests/Feature/ExampleTest.php', 'ExampleTest'],
    // A Windows editor may hand over a Windows path whatever platform Pint runs on.
    'windows separators' => ['app\Models\Team.php', 'Team'],
    'windows absolute path' => ['C:\Users\taylor\app\app\Models\Flight.php', 'Flight'],
]);

it('leaves Laravel files whose name is not a class name untouched', function (string $path, string $input, string $expected) {
    $result = Process::input($input)
        ->path(base_path('tests/Fixtures/psr-autoloading'))
        ->run('php '.base_path('pint').' --stdin-filename='.escapeshellarg($path))
        ->throw();

    expect($result->output())->toBe($expected)->and($result->output())->not->toContain('pint_stdin_');
})->with([
    'migration' => [
        'database/migrations/2024_01_01_000000_create_users_table.php',
        <<<'PHP'
        <?php

        use Illuminate\Database\Migrations\Migration;

        class CreateUsersTable extends Migration
        {
        }

        PHP,
        <<<'PHP'
        <?php

        use Illuminate\Database\Migrations\Migration;

        class CreateUsersTable extends Migration {}

        PHP,
    ],
    'routes' => [
        'routes/web.php',
        <<<'PHP'
        <?php

        Route::get('/', function () {
            return view('welcome');
        });

        PHP,
        <<<'PHP'
        <?php

        Route::get('/', function () {
            return view('welcome');
        });

        PHP,
    ],
    'config' => [
        'config/app.php',
        <<<'PHP'
        <?php

        return [
            'name' => env('APP_NAME', 'Laravel'),
        ];

        PHP,
        <<<'PHP'
        <?php

        return [
            'name' => env('APP_NAME', 'Laravel'),
        ];

        PHP,
    ],
]);

it('formats a Blade view from stdin when the Blade rule is enabled', function () {
    $input = <<<'BLADE'
    <div>
    @if(true)
    <p>Hello</p>
    @endif
    </div>

    BLADE;

    $expected = <<<'BLADE'
    <div>
        @if (true)
            <p>Hello</p>
        @endif
    </div>

    BLADE;

    $result = Process::input($input)
        ->run('php '.base_path('pint').' --config '.base_path('tests/Fixtures/stdin-blade/pint.json').' --stdin-filename=resources/views/welcome.blade.php')
        ->throw();

    expect($result->output())->toBe($expected)->and($result->output())->not->toContain('pint_stdin_');
});

it('formats code from stdin when the filename has no basename', function () {
    $input = <<<'PHP'
    <?php
    $a=1;

    PHP;

    $expected = <<<'PHP'
    <?php

    $a = 1;

    PHP;

    $result = Process::input($input)
        ->run('php '.base_path('pint').' --stdin-filename=/')
        ->throw();

    expect($result)->output()->toBe($expected)->errorOutput()->toBe('');
});

it('fails when the temporary directory cannot be created', function () {
    // A path under an existing file can never be created, whatever the user's privileges.
    $unusable = base_path('composer.json').DIRECTORY_SEPARATOR.'temp';

    $result = Process::input('<?php $a=1;')
        ->env(['TMPDIR' => $unusable, 'TMP' => $unusable, 'TEMP' => $unusable])
        ->run('php '.base_path('pint').' --quiet --stdin-filename=app/Test.php');

    expect($result->exitCode())->toBe(1)
        ->and($result->output())->toContain('Unable to create a temporary directory for [app/Test.php]')
        ->and($result->output())->not->toContain('$a = 1');
})->skipOnWindows();
