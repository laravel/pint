<?php

use App\PrettierFormatters\EscapedDirectiveSpacing;

function spaceEscaped(string $source, ?string $formatted = null): string
{
    $formatter = new EscapedDirectiveSpacing;
    $formatter->preFormat($source);

    return $formatter->postFormat($formatted ?? $source);
}

it('restores the separator prettier ate before an escaped directive', function () {
    expect(spaceEscaped("<div>\n    Text @@if more\n</div>\n", '<div>Text@@if more</div>'."\n"))
        ->toBe('<div>Text @@if more</div>'."\n");
});

it('restores the separator when prettier joined two lines', function () {
    expect(spaceEscaped("<p>\n    A\n    @@endif\n</p>\n", '<p>A@@endif</p>'."\n"))
        ->toBe('<p>A @@endif</p>'."\n");
});

it('leaves an escaped directive that already had room alone', function () {
    $in = '<div>Text @@if more</div>'."\n";

    expect(spaceEscaped($in))->toBe($in);
});

it('leaves an escaped directive that opens a line alone', function () {
    $in = "<div>\n    @@if (\$condition)\n</div>\n";

    expect(spaceEscaped($in))->toBe($in);
});

it('leaves an escaped directive glued in the source alone', function () {
    // The source already compiles the directive rather than escaping it, and
    // separating them now would change what the view renders.
    $in = '<div>Text@@if more</div>'."\n";

    expect(spaceEscaped($in))->toBe($in);
});

it('repairs a pair prettier broke even when another pair was glued in the source', function () {
    // The "user@@example" the author wrote is theirs to keep; it says nothing
    // about the "Text @@if" prettier went on to eat the separator out of.
    $source = "<?php \$mail = 'user@@example'; ?>\n<div>\n    Text @@if more\n</div>\n";
    $formatted = "<?php \$mail = 'user@@example'; ?>\n<div>Text@@if more</div>\n";

    expect(spaceEscaped($source, $formatted))
        ->toBe("<?php \$mail = 'user@@example'; ?>\n<div>Text @@if more</div>\n");
});

it('leaves every occurrence of a pair the source glued alone', function () {
    $in = '<div>Text@@if more Text@@if again</div>'."\n";

    expect(spaceEscaped($in))->toBe($in);
});

it('restores separators for consecutive escaped directives', function () {
    expect(spaceEscaped("<p>\n    a @@if b @@endif\n</p>\n", '<p>a@@if b@@endif</p>'."\n"))
        ->toBe('<p>a @@if b @@endif</p>'."\n");
});

it('does not touch an unescaped directive', function () {
    $in = "<div>\n    @if (\$condition)\n    @endif\n</div>\n";

    expect(spaceEscaped($in))->toBe($in);
});

it('is idempotent', function () {
    $source = "<div>\n    Text @@if more\n</div>\n";
    $once = spaceEscaped($source, '<div>Text@@if more</div>'."\n");

    expect(spaceEscaped($once))->toBe($once);
});
