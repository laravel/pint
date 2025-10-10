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
