<?php

use Illuminate\Support\Facades\Process;

it('formats code from stdin', function (string $input, ?string $expected) {
    $result = Process::input($input)->run('php pint app/Test.php --stdin')->throw();

    expect($result)
        ->output()->toBe($expected ?? $input)
        ->errorOutput()->toBe('');
})->with([
    'basic array and conditional' => [
        <<<'PHP'
            <?php
            $array = array("a","b");
            if($condition==true){
                echo "test";
            }
            PHP,
        <<<'PHP'
            <?php

            $array = ['a', 'b'];
            if ($condition == true) {
                echo 'test';
            }

            PHP,
    ],
    'class with method' => [
        <<<'PHP'
            <?php
            class Test{
            public function method(){
            return array("key"=>"value");
            }
            }
            PHP,
        <<<'PHP'
            <?php

            class Test
            {
                public function method()
                {
                    return ['key' => 'value'];
                }
            }

            PHP,
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

            PHP,
        null,
    ],
]);
