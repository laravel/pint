<?php

namespace App;

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\Exceptions\UnrestorableContentException;
use App\PrettierFormatters\AlpineMaskPatterns;
use App\PrettierFormatters\CollapseShortSlots;
use App\PrettierFormatters\CollapseSingleAttribute;
use App\PrettierFormatters\DedentHuggedTerminator;
use App\PrettierFormatters\EmbeddedBladeMasker;
use App\PrettierFormatters\EscapedDirectiveSpacing;
use App\PrettierFormatters\JoinDanglingCloseBracket;
use App\PrettierFormatters\JoinDanglingOpenBracket;
use App\PrettierFormatters\NotOperatorSpacing;
use App\PrettierFormatters\PhpBlockFormatting;
use App\PrettierFormatters\StripSensitiveLeadingBlankLines;
use App\Support\Prettier;

class BladeFormatter
{
    /**
     * The formatters applied around prettier's Blade output.
     *
     * @var array<int, class-string>
     */
    protected static array $formatters = [
        // Restores the separator prettier eats in front of an escaped "@@" directive.
        // Runs first so that its post-pass lands before the maskers put their own
        // "@@" back, leaving those to the pass that masked them.
        EscapedDirectiveSpacing::class,

        // Drops the blank lines prettier injects after a wrapped <pre>/<textarea> tag.
        StripSensitiveLeadingBlankLines::class,

        // Enforces Pint's "! $value" spacing inside JS/Alpine/PHP attribute values.
        NotOperatorSpacing::class,

        // Collapses a short single-body <x-slot> back onto one line.
        CollapseShortSlots::class,

        // Collapses a tag prettier wrapped solely because of one attribute.
        CollapseSingleAttribute::class,

        // Joins a closing tag's dangling ">" back onto the preceding line.
        JoinDanglingCloseBracket::class,

        // Pulls a hugged opening terminator back up onto its bare tag name line.
        JoinDanglingOpenBracket::class,

        // Re-indents a hugged opening terminator line to match its tag.
        DedentHuggedTerminator::class,

        // Masks Blade inside <script>/<style> across prettier, then restores it.
        EmbeddedBladeMasker::class,

        // Masks an Alpine "x-mask" pattern across prettier, which would otherwise
        // format the literal value as a JS expression, then restores it.
        AlpineMaskPatterns::class,

        // Runs Pint over the PHP in @php blocks, <?php islands, directives, and echoes.
        PhpBlockFormatting::class,
    ];

    /**
     * Create a new blade formatter instance.
     */
    public function __construct(
        protected Prettier $prettier,
    ) {
        //
    }

    /**
     * Format the given content.
     */
    public function format(string $path, string $content): string
    {
        $formatters = collect(static::$formatters)->map(
            fn (string $formatter): PrettierPreFormatter|PrettierPostFormatter => resolve($formatter),
        );

        $masked = $formatters->reduce(
            fn (string $content, PrettierPreFormatter|PrettierPostFormatter $formatter): string => $formatter instanceof PrettierPreFormatter
                ? $formatter->preFormat($content)
                : $content,
            $content,
        );

        $formatted = $this->prettier->format($path, $masked);

        try {
            return $formatters->reduce(
                fn (string $formatted, PrettierPreFormatter|PrettierPostFormatter $formatter): string => $formatter instanceof PrettierPostFormatter
                    ? $formatter->postFormat($formatted)
                    : $formatted,
                $formatted,
            );
        } catch (UnrestorableContentException) {
            // A pre-formatter could not undo its own work, which means prettier lost or
            // duplicated one of its placeholders. Discard the whole run and hand back the
            // untouched file: only the original content is guaranteed to be intact once a
            // masking pass has been given up on.
            return $content;
        }
    }
}
