<?php

use App\Fixers\Prettier\CssFixer;
use App\Fixers\Prettier\JsFixer;
use App\Support\Prettier;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * A prettier double whose "format" hands the path, content and options to the
 * given callback, so a fixer can be exercised without booting the node worker.
 */
function spyPrettier(Closure $handler): Prettier
{
    return new class('', $handler) extends Prettier
    {
        public function __construct(string $projectRoot, private Closure $handler)
        {
            parent::__construct($projectRoot);
        }

        public function format(string $path, string $content, array $options = []): string
        {
            return ($this->handler)($path, $content, $options);
        }
    };
}

/**
 * A prettier double that must never be reached.
 */
function unreachablePrettier(): Prettier
{
    return spyPrettier(fn (): string => throw new Exception('prettier should not be reached.'));
}

/**
 * Run the given fixer over the code, returning the fixed result.
 */
function fixWithPrettierFixer(object $fixer, string $code, string $filename): string
{
    $tokens = Tokens::fromCode($code);

    $fixer->fix(new SplFileInfo($filename), $tokens);

    return $tokens->generateCode();
}

it('formats javascript through prettier using the babel parser', function () {
    $fixer = new JsFixer(spyPrettier(
        function (string $path, string $content, array $options): string {
            expect($path)->toBe('/app/resources/js/app.js')
                ->and($options['parser'])->toBe('babel');

            // Stands in for prettier reformatting the file.
            return str_replace('var', 'let', $content);
        },
    ));

    expect(fixWithPrettierFixer($fixer, 'var x = 1', '/app/resources/js/app.js'))->toBe('let x = 1');
});

it('formats every javascript flavour with the parser it needs', function (string $filename, string $parser) {
    $fixer = new JsFixer(spyPrettier(
        function (string $path, string $content, array $options) use ($filename, $parser): string {
            expect(basename($path))->toBe($filename)
                ->and($options['parser'])->toBe($parser);

            return $content;
        },
    ));

    fixWithPrettierFixer($fixer, 'x', '/app/resources/js/'.$filename);
})->with([
    ['app.js', 'babel'],
    ['component.jsx', 'babel'],
    ['module.mjs', 'babel'],
    ['config.cjs', 'babel'],
    ['module.ts', 'typescript'],
    ['component.tsx', 'typescript'],
]);

it('formats stylesheets with the parser each extension needs', function (string $filename, string $parser) {
    $fixer = new CssFixer(spyPrettier(
        function (string $path, string $content, array $options) use ($filename, $parser): string {
            expect(basename($path))->toBe($filename)
                ->and($options['parser'])->toBe($parser);

            return $content;
        },
    ));

    fixWithPrettierFixer($fixer, 'a{color:red}', '/app/resources/css/'.$filename);
})->with([
    ['app.css', 'css'],
    ['theme.scss', 'scss'],
    ['theme.less', 'less'],
]);

it('exposes the javascript rule to the finder and the configuration', function () {
    $fixer = new JsFixer(unreachablePrettier());

    expect($fixer->getName())->toBe('Pint/prettier_js')
        ->and($fixer->finderNames())->toBe(['*.js', '*.jsx', '*.mjs', '*.cjs', '*.ts', '*.tsx'])
        ->and($fixer->prettierDependencies())->toBe(['prettier' => '^3.8.4']);
});

it('exposes the css rule to the finder and the configuration', function () {
    $fixer = new CssFixer(unreachablePrettier());

    expect($fixer->getName())->toBe('Pint/prettier_css')
        ->and($fixer->finderNames())->toBe(['*.css', '*.scss', '*.less'])
        ->and($fixer->prettierDependencies())->toBe(['prettier' => '^3.8.4']);
});

it('leaves files of other languages alone', function (object $fixer, string $filename) {
    expect(fixWithPrettierFixer($fixer, 'original', $filename))->toBe('original');
})->with(fn () => [
    'javascript on a php file' => [new JsFixer(unreachablePrettier()), '/app/app/Models/User.php'],
    'javascript on a blade view' => [new JsFixer(unreachablePrettier()), '/app/resources/views/welcome.blade.php'],
    'javascript on a stylesheet' => [new JsFixer(unreachablePrettier()), '/app/resources/css/app.css'],
    'css on a script' => [new CssFixer(unreachablePrettier()), '/app/resources/js/app.js'],
]);

it('leaves minified assets alone', function () {
    expect(fixWithPrettierFixer(new JsFixer(unreachablePrettier()), 'compressed', '/app/public/build/app.min.js'))->toBe('compressed')
        ->and(fixWithPrettierFixer(new CssFixer(unreachablePrettier()), 'compressed', '/app/public/build/app.min.css'))->toBe('compressed');
});

it('clears the tokens when prettier hands back an empty file', function () {
    $fixer = new JsFixer(spyPrettier(fn (): string => ''));

    expect(fixWithPrettierFixer($fixer, 'var x = 1', '/app/resources/js/app.js'))->toBe('');
});
