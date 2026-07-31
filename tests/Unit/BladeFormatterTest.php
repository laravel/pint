<?php

use App\BladeFormatter;
use App\Support\Prettier;

/**
 * A prettier double whose "format" is the given callback, so a formatter pipeline can be
 * exercised without booting the node worker.
 */
function fakePrettier(Closure $handler): Prettier
{
    return new class('', $handler) extends Prettier
    {
        public function __construct(string $projectRoot, private Closure $handler)
        {
            parent::__construct($projectRoot);
        }

        public function format(string $path, string $content): string
        {
            return ($this->handler)($content);
        }
    };
}

it('keeps an Alpine mask pattern out of prettier\'s reach', function () {
    // Stands in for prettier reading the value as a JS expression and spacing out the hyphen.
    $formatter = new BladeFormatter(fakePrettier(
        fn (string $content): string => str_replace('9999-999', '9999 - 999', $content),
    ));

    $in = '<div><input x-mask="9999-999" /></div>'."\n";

    expect($formatter->format('view.blade.php', $in))->toBe($in);
});

it('hands back the untouched file when a placeholder cannot be restored', function () {
    $in = implode("\n", [
        '<input x-mask="9999-999" />',
        '<script>',
        '    let total = {{ $total }};',
        '</script>',
        '',
    ]);

    // Prettier loses the mask placeholder. Every masking pass has to be given up on at that
    // point: the intermediate content still holds the other pass' placeholders, so only the
    // original file is guaranteed to be intact.
    $formatter = new BladeFormatter(fakePrettier(
        fn (string $content): string => (string) preg_replace('/pm\d+_*/', 'gone', $content),
    ));

    $out = $formatter->format('view.blade.php', $in);

    expect($out)->toBe($in);
    expect($out)->not->toContain('__PINT_BLADE_');
    expect($out)->not->toContain('gone');
});

it('hands back the untouched file when an embedded Blade placeholder cannot be restored', function () {
    $in = implode("\n", [
        '<input x-mask="9999-999" />',
        '<script>',
        '    let total = {{ $total }};',
        '</script>',
        '',
    ]);

    $formatter = new BladeFormatter(fakePrettier(
        fn (string $content): string => (string) preg_replace('/__PINT_BLADE_\d+__/', 'gone', $content),
    ));

    $out = $formatter->format('view.blade.php', $in);

    expect($out)->toBe($in);
    expect($out)->not->toContain('pm0');
    expect($out)->not->toContain('gone');
});
