<?php

namespace App;

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\PrettierFormatters\CollapseShortSlots;
use App\PrettierFormatters\CollapseSingleAttribute;
use App\PrettierFormatters\DedentHuggedTerminator;
use App\PrettierFormatters\DirectiveTrailingCommas;
use App\PrettierFormatters\EmbeddedBladeMasker;
use App\PrettierFormatters\JoinDanglingCloseBracket;
use App\PrettierFormatters\JoinDanglingOpenBracket;
use App\PrettierFormatters\NotOperatorSpacing;
use App\PrettierFormatters\PhpBlockFormatting;
use App\PrettierFormatters\StripSensitiveLeadingBlankLines;
use App\Support\Prettier;

class BladeFormatter
{
    /**
     * The placeholder to original-text map.
     *
     * @var array<string, string>
     */
    private array $ignoreRangeMap = [];

    /**
     * The content as it entered protectIgnoreRanges().
     */
    private string $ignoreRangeOriginal = '';

    /**
     * The index used to build unique placeholder tokens.
     */
    private int $ignoreRangeCounter = 0;

    /**
     * The formatters applied around prettier's Blade output.
     *
     * @var array<int, class-string>
     */
    protected static array $formatters = [
        // Drops the blank lines prettier injects after a wrapped <pre>/<textarea> tag.
        StripSensitiveLeadingBlankLines::class,

        // Adds a trailing comma to a directive's wrapped call/array arguments.
        DirectiveTrailingCommas::class,

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
        $ranges = $this->prettier->ignoreRanges($path, $content);
        $content = $this->protectIgnoreRanges($content, $ranges, $content);

        $formatters = collect(static::$formatters)->map(
            fn (string $formatter): PrettierPreFormatter|PrettierPostFormatter => resolve($formatter),
        );

        $content = $formatters->reduce(
            fn (string $content, PrettierPreFormatter|PrettierPostFormatter $formatter): string => $formatter instanceof PrettierPreFormatter
                ? $formatter->preFormat($content)
                : $content,
            $content,
        );

        $content = $this->restoreIgnoreRanges($content);

        if ($ranges === []) {
            $formatted = $this->prettier->format($path, $content);
        } else {
            $result = $this->prettier->formatWithIgnoreRanges($path, $content);
            $formatted = $this->protectIgnoreRanges($result['formatted'], $result['ranges'], $content);
        }

        $formatted = $formatters->reduce(
            fn (string $formatted, PrettierPreFormatter|PrettierPostFormatter $formatter): string => $formatter instanceof PrettierPostFormatter
                ? $formatter->postFormat($formatted)
                : $formatted,
            $formatted,
        );

        return $this->restoreIgnoreRanges($formatted);
    }

    /**
     * Protect the given formatter ignore ranges.
     *
     * @param  array<int, array{start: int, end: int, sourceStart?: int, sourceEnd?: int}>  $ranges
     */
    private function protectIgnoreRanges(string $content, array $ranges, string $source): string
    {
        $this->ignoreRangeMap = [];
        $this->ignoreRangeOriginal = $content;
        $this->ignoreRangeCounter = 0;

        if ($ranges === []) {
            return $content;
        }

        $result = '';
        $cursor = 0;

        foreach ($ranges as $range) {
            $token = $this->makeIgnoreRangeToken();
            $sourceStart = $range['sourceStart'] ?? $range['start'];
            $sourceEnd = $range['sourceEnd'] ?? $range['end'];

            $this->ignoreRangeMap[$token] = substr($source, $sourceStart, $sourceEnd - $sourceStart);
            $result .= substr($content, $cursor, $range['start'] - $cursor).$token;
            $cursor = $range['end'];
        }

        return $result.substr($content, $cursor);
    }

    /**
     * Restore the contents of formatter ignore ranges.
     */
    private function restoreIgnoreRanges(string $content): string
    {
        if ($this->ignoreRangeMap === []) {
            $this->ignoreRangeOriginal = '';

            return $content;
        }

        $map = $this->ignoreRangeMap;
        $original = $this->ignoreRangeOriginal;

        $this->ignoreRangeMap = [];
        $this->ignoreRangeOriginal = '';

        foreach (array_keys($map) as $token) {
            if (substr_count($content, $token) !== 1) {
                return $original;
            }
        }

        return str_replace(array_keys($map), array_values($map), $content);
    }

    /**
     * Build a unique placeholder token.
     */
    private function makeIgnoreRangeToken(): string
    {
        while (true) {
            $token = sprintf('__PINT_BLADE_IGNORE_%d__', $this->ignoreRangeCounter++);

            if (! str_contains($this->ignoreRangeOriginal, $token)) {
                return $token;
            }
        }
    }
}
