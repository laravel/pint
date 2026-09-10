<?php

use App\PrettierFormatters\PhpBlockFormatting;
use App\Support\PhpFragmentFormatter;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Tokenizer\Tokens;

function phpBlockFormatting(): PhpBlockFormatting
{
    return new PhpBlockFormatting(new PhpFragmentFormatter);
}

/**
 * A formatter whose only rule punctuates multi-line arrays *and* arguments.
 *
 * The "laravel" preset only punctuates arrays, but a project may opt
 * "arguments" into "trailing_comma_in_multiline", which then sees the synthetic
 * "__pint__(...)" host as a multi-line call.
 */
function phpBlockFormattingWithTrailingCommas(): PhpBlockFormatting
{
    return new PhpBlockFormatting(new class extends PhpFragmentFormatter
    {
        public function format(string $code, bool $fragment = false): string
        {
            $fixer = new TrailingCommaInMultilineFixer;
            $fixer->configure(['elements' => ['arrays', 'arguments']]);

            $tokens = Tokens::fromCode($code);
            $fixer->fix(new SplFileInfo('fragment.php'), $tokens);

            return $tokens->generateCode();
        }
    });
}

/**
 * A formatter that applies no rule at all.
 *
 * Whatever comes back out of it is this class's own rewriting, with nothing
 * from php-cs-fixer mixed in.
 */
function phpBlockFormattingWithoutRules(): PhpBlockFormatting
{
    return new PhpBlockFormatting(new class extends PhpFragmentFormatter
    {
        public function format(string $code, bool $fragment = false): string
        {
            return $code;
        }
    });
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

it('leaves a nested multiline directive argument untouched when no fixer re-indents it', function () {
    $in = <<<'BLADE'
    <div>
        <div
            @class([
                'button',
                'button--active' => $isActive,
            ])
        ></div>

        @include('partials.card', [
            'title' => $title,
        ])
    </div>
    BLADE;

    expect(phpBlockFormattingWithoutRules()->postFormat($in))->toBe($in);
});

it('does not leave a trailing comma on a multiline directive argument', function () {
    // Blade compiles the argument straight into "if (...):", where a trailing
    // comma is a syntax error, so it never belongs to the argument itself.
    $in = <<<'BLADE'
    @if (
        ($user->isAdmin() || $user->isOwner())
            && $user->isActive()
    )
        <span>Admin</span>
    @endif
    BLADE;

    expect(phpBlockFormattingWithTrailingCommas()->postFormat($in))->toBe($in);
});

it('keeps the trailing comma of an array inside a multiline directive argument', function () {
    $in = <<<'BLADE'
    @include('partials.card', [
        'title' => $title,
    ])
    BLADE;

    expect(phpBlockFormattingWithTrailingCommas()->postFormat($in))->toBe($in);
});

it('does not leave a trailing comma on an indented multiline directive argument', function () {
    // The closing ")" belongs to the directive, so it stays at the directive's
    // own indentation rather than being flung back to column zero.
    $in = <<<'BLADE'
    <div>
        @if (
            ($user->isAdmin() || $user->isOwner())
                && $user->isActive()
        )
            <span>Admin</span>
        @endif
    </div>
    BLADE;

    expect(phpBlockFormattingWithTrailingCommas()->postFormat($in))->toBe($in);
});

it('does not leave a trailing comma behind a comment closing a directive argument', function (string $comment) {
    // The comma is punctuated onto the code, not onto the line, so a comment
    // after it means the comma is no longer the argument's last character.
    $in = sprintf(<<<'BLADE'
    @if (
        $user->isAdmin()
        && $user->isActive() %s
    )
        <span>Admin</span>
    @endif
    BLADE, $comment);

    expect(phpBlockFormattingWithTrailingCommas()->postFormat($in))->toBe($in);
})->with([
    '// admins only',
    '# admins only',
    '/* admins only */',
]);

it('does not leave a trailing comma behind a comment on its own line', function () {
    $in = <<<'BLADE'
    @if (
        $user->isAdmin()
        // nothing else matters
    )
        <span>Admin</span>
    @endif
    BLADE;

    expect(phpBlockFormattingWithTrailingCommas()->postFormat($in))->toBe($in);
});

it('keeps a comma that only lives inside a comment', function () {
    // No fixer ran, so there is no host comma to drop, and the comment's own
    // punctuation is the author's prose.
    $in = <<<'BLADE'
    @if (
        $user->isAdmin() // admins, owners,
    )
        <span>Admin</span>
    @endif
    BLADE;

    expect(phpBlockFormattingWithoutRules()->postFormat($in))->toBe($in);
});

it('keeps a trailing comma the directive argument already carried', function () {
    // Only the comma the synthetic host invited is dropped; this one is the
    // author's, and "@class" compiles into a call where it is legal.
    $in = <<<'BLADE'
    @class([
        'button',
    ],)
    BLADE;

    expect(phpBlockFormattingWithoutRules()->postFormat($in))->toBe($in);
});
