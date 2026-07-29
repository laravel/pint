<?php

namespace App;

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\Exceptions\PrettierException;
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
        $original = $content;
        $ranges = $this->prettier->ignoreRanges($path, $content);
        $formatters = collect(static::$formatters)->map(
            fn (string $formatter): PrettierPreFormatter|PrettierPostFormatter => resolve($formatter),
        );

        try {
            $content = $this->protectIgnoreRanges($content, $ranges, $content);

            $masked = $formatters->reduce(
                fn (string $content, PrettierPreFormatter|PrettierPostFormatter $formatter): string => $formatter instanceof PrettierPreFormatter
                    ? $formatter->preFormat($content)
                    : $content,
                $content,
            );

            $content = $this->restoreIgnoreRanges($masked);

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
        } catch (UnrestorableContentException) {
            // A masking pass could not undo its own work, which means prettier lost or
            // duplicated one of its placeholders. Discard the whole run and hand back the
            // untouched file: only the original content is guaranteed to be intact once a
            // masking pass has been given up on.
            $this->ignoreRangeMap = [];
            $this->ignoreRangeOriginal = '';

            return $original;
        }
    }

    /**
     * Protect the given formatter ignore ranges.
     *
     * @param  array<int, mixed>  $ranges
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
        $sourceCursor = 0;
        $contentLength = strlen($content);
        $sourceLength = strlen($source);

        foreach ($ranges as $range) {
            [$start, $end, $sourceStart, $sourceEnd] = $this->parseIgnoreRange(
                $range,
                $cursor,
                $sourceCursor,
                $contentLength,
                $sourceLength,
            );

            $token = $this->makeIgnoreRangeToken();
            $this->ignoreRangeMap[$token] = substr($source, $sourceStart, $sourceEnd - $sourceStart);
            $result .= substr($content, $cursor, $start - $cursor).$token;
            $cursor = $end;
            $sourceCursor = $sourceEnd;
        }

        return $result.substr($content, $cursor);
    }

    /**
     * Validate and return a formatter ignore range.
     *
     * @return array{int, int, int, int}
     */
    private function parseIgnoreRange(mixed $range, int $cursor, int $sourceCursor, int $contentLength, int $sourceLength): array
    {
        if (! is_array($range)
            || ! isset($range['start'], $range['end'], $range['sourceStart'], $range['sourceEnd'])
            || ! is_int($range['start'])
            || ! is_int($range['end'])
            || ! is_int($range['sourceStart'])
            || ! is_int($range['sourceEnd'])) {
            throw new PrettierException('Laravel Pint\'s Prettier worker returned invalid Blade ignore ranges.');
        }

        if ($range['start'] < $cursor
            || $range['end'] < $range['start']
            || $range['end'] > $contentLength
            || $range['sourceStart'] < $sourceCursor
            || $range['sourceEnd'] < $range['sourceStart']
            || $range['sourceEnd'] > $sourceLength) {
            throw new PrettierException('Laravel Pint\'s Prettier worker returned invalid Blade ignore ranges.');
        }

        return [$range['start'], $range['end'], $range['sourceStart'], $range['sourceEnd']];
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

        $this->ignoreRangeMap = [];
        $this->ignoreRangeOriginal = '';

        foreach (array_keys($map) as $token) {
            if (substr_count($content, $token) !== 1) {
                throw new UnrestorableContentException;
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
