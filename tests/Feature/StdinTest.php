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
