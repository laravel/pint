<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;
use App\Support\PhpFragmentFormatter;
use Illuminate\Support\Str;

class PhpBlockFormatting implements PrettierPostFormatter
{
    /**
     * Directives whose argument must be wrapped in a control structure before formatting.
     *
     * @var array<string, string>
     */
    private const CONTROL_DIRECTIVES = [
        'for' => 'for',
        'foreach' => 'foreach',
        'forelse' => 'foreach',
    ];

    /**
     * Directive names that must be left untouched.
     *
     * @var array<int, string>
     */
    private const SKIP_DIRECTIVES = [
        'php', 'media', 'supports', 'scope', 'keyframes', 'font', 'import', 'charset',
        'namespace', 'page', 'container', 'layer', 'property', 'apply', 'tailwind',
    ];

    public function __construct(
        protected PhpFragmentFormatter $formatter,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function postFormat(string $content): string
    {
        $verbatim = $this->verbatimSpans($content);

        $edits = $this->regionEdits($content, $verbatim);
        $edits = array_merge($edits, $this->directiveEdits($content, $edits, $verbatim));
        $edits = array_merge($edits, $this->echoEdits($content, $edits, $verbatim));
        $edits = array_merge($edits, $this->attributeEdits($content, $edits, $verbatim));

        usort($edits, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $result = '';
        $cursor = 0;

        foreach ($edits as [$offset, $length, $replacement]) {
            if ($offset < $cursor) {
                continue;
            }

            $result .= substr($content, $cursor, $offset - $cursor).$replacement;
            $cursor = $offset + $length;
        }

        return $result.substr($content, $cursor);
    }

    /**
     * Build the edits that reformat every "@php ... @endphp" block and "<?php ... ?>" island.
     *
     * @param  array<int, array{int, int}>  $verbatim
     * @return array<int, array{int, int, string}>
     */
    private function regionEdits(string $content, array $verbatim): array
    {
        $pattern = '/@php(?![\w(]).*?@endphp|<\?php.*?\?>/is';

        if (! preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $edits = [];

        foreach ($matches[0] as [$region, $offset]) {
            if ($this->within($offset, $verbatim)) {
                continue;
            }

            $edits[] = [$offset, strlen($region), $this->formatRegion($region, $this->indentAt($content, $offset))];
        }

        return $edits;
    }

    /**
     * Build the edits that reformat the PHP argument of every directive.
     *
     * @param  array<int, array{int, int, string}>  $regions
     * @param  array<int, array{int, int}>  $verbatim
     * @return array<int, array{int, int, string}>
     */
    private function directiveEdits(string $content, array $regions, array $verbatim): array
    {
        if (! preg_match_all('/@([a-zA-Z_]\w*)\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $spans = collect($regions)
            ->map(fn (array $edit): array => [$edit[0], $edit[0] + $edit[1]])
            ->merge($verbatim)
            ->all();

        $edits = [];

        foreach ($matches[0] as $index => [$match, $start]) {
            $name = $matches[1][$index][0];

            if (in_array(strtolower($name), self::SKIP_DIRECTIVES, true) || $this->within($start, $spans)) {
                continue;
            }

            $open = $start + strlen($match) - 1;
            $close = $this->matchParen($content, $open);

            if ($close === null) {
                continue;
            }

            $arg = substr($content, $open + 1, $close - $open - 1);
            $formatted = $this->formatDirectiveArg($name, $arg, $this->lineIndentAt($content, $start));

            if ($formatted !== $arg) {
                $edits[] = [$open + 1, $close - $open - 1, $formatted];
            }
        }

        return $edits;
    }

    /**
     * Format a directive's argument with Pint.
     */
    private function formatDirectiveArg(string $name, string $arg, string $indent): string
    {
        if (trim($arg) === '') {
            return $arg;
        }

        if ($keyword = self::CONTROL_DIRECTIVES[strtolower($name)] ?? null) {
            $host = $keyword.' ('.$arg.') {}';
        } else {
            $host = '__pint__('.$arg.');';
        }

        $formatted = $this->stripPhpWrapper($this->formatter->format("<?php\n".$host."\n", fragment: true));

        $open = strpos($formatted, '(');

        if ($open === false || ($close = $this->matchParen($formatted, $open)) === null) {
            return $arg;
        }

        $inner = substr($formatted, $open + 1, $close - $open - 1);

        return $this->reindentArg($inner, $indent);
    }

    /**
     * Re-indent the continuation lines of a formatted argument.
     */
    private function reindentArg(string $inner, string $indent): string
    {
        return Str::of($inner)
            ->explode("\n")
            ->map(fn (string $line, int $index): string => $index === 0 || $line === '' ? $line : $indent.$line)
            ->implode("\n");
    }

    /**
     * Build the edits that reformat every multi-line "{{ ... }}" / "{!! ... !!}" echo.
     *
     * @param  array<int, array{int, int, string}>  $regions
     * @param  array<int, array{int, int}>  $verbatim
     * @return array<int, array{int, int, string}>
     */
    private function echoEdits(string $content, array $regions, array $verbatim): array
    {
        if (! preg_match_all('/\{\{(?!--)(.*?)\}\}|\{!!(.*?)!!\}/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $spans = collect($regions)
            ->map(fn (array $edit): array => [$edit[0], $edit[0] + $edit[1]])
            ->merge($verbatim)
            ->merge($this->commentSpans($content))
            ->all();

        $edits = [];

        foreach ($matches[0] as $index => [$region, $start]) {
            $unescaped = $matches[1][$index][1] === -1;
            $expr = $unescaped ? $matches[2][$index][0] : $matches[1][$index][0];

            if (! str_contains($region, "\n") || $this->within($start, $spans)) {
                continue;
            }

            // "@{{ ... }}" is an escaped literal echo emitted verbatim, not PHP.
            if ($start > 0 && $content[$start - 1] === '@') {
                continue;
            }

            $formatted = $this->formatEcho($expr, $this->lineIndentAt($content, $start), $unescaped);

            if ($formatted !== null && $formatted !== $region) {
                $edits[] = [$start, strlen($region), $formatted];
            }
        }

        return $edits;
    }

    /**
     * Reformat the expression of a multi-line echo with Pint and lay it out beneath the echo.
     */
    private function formatEcho(string $expr, string $indent, bool $unescaped): ?string
    {
        if (trim($expr) === '') {
            return null;
        }

        // De-indent to column zero so Pint's output stays idempotent across runs.
        $core = $this->deindent(trim($expr, "\r\n"));

        $formatted = $this->stripPhpWrapper($this->formatter->format("<?php\n".$core.";\n", fragment: true));
        $formatted = (string) preg_replace('/;$/', '', $formatted);

        if (trim($formatted) === '') {
            return null;
        }

        $inner = $this->reindent($formatted, $indent.'    ');

        [$head, $tail] = $unescaped ? ['{!!', '!!}'] : ['{{', '}}'];

        return $head."\n".$inner."\n".$indent.$tail;
    }

    /**
     * The component tag-name prefixes whose colon-bound attributes hold PHP.
     *
     * @var array<int, string>
     */
    private const COMPONENT_PREFIXES = ['x', 's', 'statamic', 'flux', 'livewire', 'native'];

    /**
     * Build the edits that reformat the PHP expression of every colon-bound attribute on a Blade component.
     *
     * @param  array<int, array{int, int, string}>  $regions
     * @param  array<int, array{int, int}>  $verbatim
     * @return array<int, array{int, int, string}>
     */
    private function attributeEdits(string $content, array $regions, array $verbatim): array
    {
        $tags = $this->componentTagSpans($content);

        if ($tags === []) {
            return [];
        }

        $skip = collect($regions)
            ->map(fn (array $edit): array => [$edit[0], $edit[0] + $edit[1]])
            ->merge($verbatim)
            ->all();

        if (! preg_match_all('/(?<![\w:-])(:[\w.:-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $edits = [];

        foreach ($matches[0] as $index => [$whole, $start]) {
            $name = $matches[1][$index][0];

            // "::attr" is an escaped literal attribute, not a bound expression.
            if (str_starts_with($name, '::') || ! $this->within($start, $tags) || $this->within($start, $skip)) {
                continue;
            }

            [$value, $valueStart] = $matches[2][$index][1] !== -1 ? $matches[2][$index] : $matches[3][$index];

            $formatted = $this->formatAttributeExpr($value);

            if ($formatted !== null && $formatted !== $value) {
                $edits[] = [$valueStart, strlen($value), $formatted];
            }
        }

        return $edits;
    }

    /**
     * The [start, end) spans of every Blade component open tag.
     *
     * @return array<int, array{int, int}>
     */
    private function componentTagSpans(string $content): array
    {
        $prefixes = implode('|', self::COMPONENT_PREFIXES);

        if (! preg_match_all('/<(?:'.$prefixes.')(?:-|:)[\w.:-]*/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        return collect($matches[0])
            ->map(fn (array $match): array => [$match[1], $this->tagEnd($content, $match[1] + strlen($match[0]))])
            ->all();
    }

    /**
     * Find the offset of the ">" that closes the tag whose attribute list starts at $cursor.
     */
    private function tagEnd(string $content, int $cursor): int
    {
        $length = strlen($content);
        $quote = null;

        for ($offset = $cursor; $offset < $length; $offset++) {
            $char = $content[$offset];

            if ($quote !== null) {
                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '>') {
                return $offset;
            }
        }

        return $length;
    }

    /**
     * Format a colon-bound attribute's PHP expression.
     */
    private function formatAttributeExpr(string $expr): ?string
    {
        if (trim($expr) === '' || str_contains($expr, '{{')) {
            return null;
        }

        $formatted = $this->stripPhpWrapper($this->formatter->format("<?php\n__pint__(".$expr.");\n", fragment: true));

        $open = strpos($formatted, '(');

        if ($open === false || ($close = $this->matchParen($formatted, $open)) === null) {
            return null;
        }

        $inner = substr($formatted, $open + 1, $close - $open - 1);

        return str_contains($inner, "\n") ? null : $inner;
    }

    /**
     * The [start, end) spans of the "{{-- --}}" Blade comments in the content.
     *
     * @return array<int, array{int, int}>
     */
    private function commentSpans(string $content): array
    {
        if (! preg_match_all('/\{\{--.*?--\}\}/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        return collect($matches[0])
            ->map(fn (array $match): array => [$match[1], $match[1] + strlen($match[0])])
            ->all();
    }

    /**
     * The [start, end) spans of the "@verbatim ... @endverbatim" blocks.
     *
     * @return array<int, array{int, int}>
     */
    private function verbatimSpans(string $content): array
    {
        if (! preg_match_all('/@verbatim\b.*?@endverbatim/is', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        return collect($matches[0])
            ->map(fn (array $match): array => [$match[1], $match[1] + strlen($match[0])])
            ->all();
    }

    /**
     * Find the offset of the ")" that closes the "(" at $open.
     */
    private function matchParen(string $content, int $open): ?int
    {
        $depth = 0;
        $quote = null;
        $length = strlen($content);

        for ($offset = $open; $offset < $length; $offset++) {
            $char = $content[$offset];

            if ($quote !== null) {
                if ($char === '\\') {
                    $offset++;
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')' && --$depth === 0) {
                return $offset;
            }
        }

        return null;
    }

    /**
     * Whether the given offset falls inside any of the [start, end) spans.
     *
     * @param  array<int, array{int, int}>  $spans
     */
    private function within(int $offset, array $spans): bool
    {
        foreach ($spans as [$start, $end]) {
            if ($offset >= $start && $offset < $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * The leading whitespace of the line the given offset sits on.
     */
    private function lineIndentAt(string $content, int $offset): string
    {
        $lineStart = strrpos(substr($content, 0, $offset), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;

        preg_match('/^[ \t]*/', substr($content, $lineStart), $matches);

        return $matches[0];
    }

    /**
     * The leading whitespace of the line the region starts on, or null when preceded by content.
     */
    private function indentAt(string $content, int $offset): ?string
    {
        $lineStart = strrpos(substr($content, 0, $offset), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;

        $prefix = substr($content, $lineStart, $offset - $lineStart);

        return trim($prefix) === '' ? $prefix : null;
    }

    /**
     * Reformat a single matched region.
     */
    private function formatRegion(string $region, ?string $indent): string
    {
        if ($indent === null) {
            return $region;
        }

        if (preg_match('/^@php(.*)@endphp$/is', $region, $matches) === 1) {
            return $this->formatPhpBlock($matches[1], $indent);
        }

        return $this->formatIsland($region, $indent);
    }

    /**
     * Format the body of an "@php ... @endphp" block and re-indent it beneath the directive.
     */
    private function formatPhpBlock(string $body, string $indent): string
    {
        $core = $this->dedent(trim($body, "\r\n"));

        if (trim($core) === '') {
            return str_contains($body, "\n") ? '@php @endphp' : '@php'.$body.'@endphp';
        }

        $statements = $this->stripPhpWrapper($this->formatter->format("<?php\n".$core."\n", fragment: true));

        if (! str_contains($body, "\n") && ! str_contains($statements, "\n")) {
            return '@php '.$statements.' @endphp';
        }

        $reindented = $this->reindent($statements, $indent.'    ');

        return '@php'."\n".$reindented."\n".$indent.'@endphp';
    }

    /**
     * Format a "<?php ... ?>" island and re-indent its continuation lines to the island's column.
     */
    private function formatIsland(string $region, string $indent): string
    {
        $formatted = rtrim($this->formatter->format($region));

        if ($indent === '') {
            return $formatted;
        }

        return Str::of($formatted)
            ->explode("\n")
            ->map(fn (string $line, int $index): string => $index === 0 || $line === '' ? $line : $indent.$line)
            ->implode("\n");
    }

    /**
     * Strip the synthetic "<?php" opener and trailing whitespace, leaving bare statements.
     */
    private function stripPhpWrapper(string $formatted): string
    {
        return rtrim((string) preg_replace('/^<\?php\s*\n/', '', $formatted));
    }

    /**
     * Strip the leading whitespace from every line, collapsing the text to column zero.
     */
    private function deindent(string $text): string
    {
        return Str::of($text)
            ->explode("\n")
            ->map(fn (string $line): string => ltrim($line, " \t"))
            ->implode("\n");
    }

    /**
     * Remove the common leading-whitespace prefix shared by every non-blank line.
     */
    private function dedent(string $text): string
    {
        $lines = Str::of($text)->explode("\n");

        $shortestIndent = $lines
            ->reject(fn (string $line): bool => trim($line) === '')
            ->map(fn (string $line): int => strlen($line) - strlen(ltrim($line, " \t")))
            ->min();

        if (! $shortestIndent) {
            return $text;
        }

        return $lines
            ->map(fn (string $line): string => trim($line) === '' ? $line : substr($line, $shortestIndent))
            ->implode("\n");
    }

    /**
     * Prefix every non-blank line with the given indentation.
     */
    private function reindent(string $text, string $prefix): string
    {
        return Str::of($text)
            ->explode("\n")
            ->map(fn (string $line): string => $line === '' ? $line : $prefix.$line)
            ->implode("\n");
    }
}
