<?php

use App\PrettierFormatters\PhpBlockFormatting;
use App\Support\PhpFragmentFormatter;

function phpBlockFormatting(): PhpBlockFormatting
{
    return new PhpBlockFormatting(new PhpFragmentFormatter);
}

it('leaves a brace control structure split across raw-php islands untouched', function () {
    $in = <<<'BLADE'
    <div>
        <?php if ($admin) { ?>
        <span>Admin</span>
        <?php } ?>
    </div>
    BLADE;

    expect(phpBlockFormatting()->postFormat($in))->toBe($in);
});

it('leaves an alternative-syntax control structure split across raw-php islands untouched', function () {
    $in = <<<'BLADE'
    <div>
        <?php if ($admin): ?>
        <span>Admin</span>
        <?php endif; ?>
    </div>
    BLADE;

    expect(phpBlockFormatting()->postFormat($in))->toBe($in);
});

it('leaves an elseif/else chain split across raw-php islands untouched', function () {
    $in = <<<'BLADE'
    <div>
        <?php if ($admin) { ?>
        <span>Admin</span>
        <?php } elseif ($editor) { ?>
        <span>Editor</span>
        <?php } else { ?>
        <span>Guest</span>
        <?php } ?>
    </div>
    BLADE;

    expect(phpBlockFormatting()->postFormat($in))->toBe($in);
});

it('leaves a loop split across raw-php islands untouched', function () {
    $in = <<<'BLADE'
    <ul>
        <?php foreach ($users as $user) { ?>
        <li>x</li>
        <?php } ?>
    </ul>
    BLADE;

    expect(phpBlockFormatting()->postFormat($in))->toBe($in);
});

it('collapses an empty @php block to the inline form so @endphp is not stranded', function () {
    // Prettier indents "@php" but leaves "@endphp" at column 0 for an empty block.
    $in = "<div>\n    @php\n@endphp\n</div>\n";

    expect(phpBlockFormatting()->postFormat($in))->toBe("<div>\n    @php @endphp\n</div>\n");
});

it('leaves an already-inline empty @php block untouched', function () {
    $in = "<div>\n    @php @endphp\n</div>\n";

    expect(phpBlockFormatting()->postFormat($in))->toBe($in);
});

it('keeps a whitespace-only empty @php block collapsed and idempotent', function () {
    $formatter = phpBlockFormatting();

    $once = $formatter->postFormat("<div>\n    @php\n\n    @endphp\n</div>\n");

    expect($once)->toBe("<div>\n    @php @endphp\n</div>\n")
        ->and($formatter->postFormat($once))->toBe($once);
});
