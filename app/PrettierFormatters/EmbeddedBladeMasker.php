<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\Exceptions\UnrestorableContentException;

class EmbeddedBladeMasker implements PrettierPostFormatter, PrettierPreFormatter
{
    /**
     * The block-opening directives.
     *
     * @var array<int, string>
     */
    private const BLOCK_OPENERS = ['if', 'unless', 'foreach', 'for', 'while', 'isset', 'switch'];

    /**
     * The block-closing directives.
     *
     * @var array<int, string>
     */
    private const BLOCK_CLOSERS = [
        'endif', 'endunless', 'endforeach', 'endfor', 'endwhile', 'endisset', 'endempty', 'endswitch',
    ];

    /**
     * The placeholder to original-text map.
     *
     * @var array<string, string>
     */
    private array $map = [];

    /**
     * The escaped-"@@" placeholder for each position shape ("bare" and "quoted").
     *
     * @var array<string, string>
     */
    private array $escapedTokens = [];

    /**
     * How many escaped "@@" each of those placeholders stands in for.
     *
     * @var array<string, int>
     */
    private array $escapedCounts = [];

    /**
     * The content as it entered preFormat().
     */
    private string $original = '';

    /**
     * The index used to build unique placeholder tokens.
     */
    private int $counter = 0;

    /**
     * {@inheritDoc}
     */
    public function preFormat(string $content): string
    {
        $this->map = [];
        $this->escapedTokens = [];
        $this->escapedCounts = [];
        $this->original = $content;
        $this->counter = 0;

        return (string) preg_replace_callback(
            '/(<(style|script)\b(?:"[^"]*"|\'[^\']*\'|[^>])*>)(.*?)(<\/\2>)/is',
            function (array $matches): string {
                $isCss = strtolower($matches[2]) === 'style';

                return $matches[1].$this->maskRegion($matches[3], $isCss).$matches[4];
            },
            $content,
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws UnrestorableContentException
     */
    public function postFormat(string $content): string
    {
        if ($this->map === [] && $this->escapedCounts === []) {
            return $content;
        }

        // Every token must appear exactly once. When one does not, restoring would emit a
        // half-masked file, so bail out and let the whole run be discarded.
        foreach (array_keys($this->map) as $token) {
            if (substr_count($content, $token) !== 1) {
                throw new UnrestorableContentException;
            }
        }

        // An escaped-"@@" placeholder is shared by every occurrence it masked, so it must come
        // back with that exact count instead.
        foreach ($this->escapedCounts as $token => $count) {
            if (substr_count($content, $token) !== $count) {
                throw new UnrestorableContentException;
            }
        }

        $restore = $this->map;

        foreach (array_keys($this->escapedCounts) as $token) {
            $restore[$token] = '@@';
        }

        return str_replace(array_keys($restore), array_values($restore), $content);
    }

    /**
     * Mask every maskable Blade construct inside the given region.
     */
    private function maskRegion(string $region, bool $isCss): string
    {
        $length = strlen($region);
        $offset = 0;
        $result = '';

        while ($offset < $length) {
            $char = $region[$offset];

            // An escaped "@@" renders as a literal "@" and is not a directive, but prettier's blade
            // plugin still re-indents the whole raw-text block around it — a little further on every
            // pass, so the file never converges. Mask it so prettier cannot see it.
            if ($char === '@' && $offset + 1 < $length && $region[$offset + 1] === '@') {
                $result .= $this->escapedAtToken(quoted: false);
                $offset += 2;

                continue;
            }

            if ($this->isStringDelimiter($char, $isCss)) {
                $end = $this->scanStringLiteral($region, $length, $offset, $char);
                $literal = substr($region, $offset, $end - $offset);

                $result .= $this->containsEcho($literal)
                    ? $this->mask($literal, $isCss ? 'value' : 'js-expression')
                    : $this->maskEscapedAt($literal);

                $offset = $end;

                continue;
            }

            $span = $this->matchConstruct($region, $offset, $length);

            if ($span === null) {
                $result .= $char;
                $offset++;

                continue;
            }

            $context = $isCss
                ? $this->cssContext($region, $offset)
                : $this->jsContext($region, $offset);

            $result .= $this->mask(substr($region, $offset, $span), $context);
            $offset += $span;
        }

        return $result;
    }

    /**
     * Mask every escaped "@@" left inside a string literal that carries no Blade echo.
     */
    private function maskEscapedAt(string $literal): string
    {
        $count = substr_count($literal, '@@');

        if ($count === 0) {
            return $literal;
        }

        return str_replace('@@', $this->escapedAtToken(quoted: true, count: $count), $literal);
    }

    /**
     * Reserve the escaped-"@@" placeholder for the given position and record how many
     * occurrences of "@@" it now stands in for.
     */
    private function escapedAtToken(bool $quoted, int $count = 1): string
    {
        $shape = $quoted ? 'quoted' : 'bare';

        $token = $this->escapedTokens[$shape] ??= $this->buildEscapedAtToken($quoted);

        $this->escapedCounts[$token] = ($this->escapedCounts[$token] ?? 0) + $count;

        return $token;
    }

    /**
     * Build the escaped-"@@" placeholder for the given position, widening it until the source no
     * longer contains it.
     *
     * Unlike the other placeholders this one is two characters wide, exactly like the "@@" it
     * replaces, so the raw-text block keeps its original line lengths and prettier makes the same
     * wrapping decisions it would have made for the unmasked source. That is also why a single
     * placeholder is shared by every occurrence rather than one being minted per "@@".
     *
     * A quoted placeholder leads with a digit so that it is neither a valid identifier nor a valid
     * number, which stops prettier's "quoteProps: as-needed" from dropping the quotes when the
     * string sits in an object-key position. A bare one is a plain identifier so that it still
     * parses where the "@@" it replaces used to sit. Neither uses a character that appears in the
     * "__PINT_BLADE_n__" or "--pint-blade-n" forms, so it can never hide inside another placeholder.
     */
    private function buildEscapedAtToken(bool $quoted): string
    {
        $token = $quoted ? '0z' : 'zz';

        while (str_contains($this->original, $token)) {
            $token .= 'z';
        }

        return $token;
    }

    /**
     * Record the snippet under a fresh placeholder token and return that token.
     */
    private function mask(string $original, string $context): string
    {
        $token = $this->makeToken($context);

        $this->map[$token] = $original;

        return $token;
    }

    /**
     * Determine the length of the maskable construct starting at $offset, if any.
     */
    private function matchConstruct(string $content, int $offset, int $length): ?int
    {
        $char = $content[$offset];

        // Raw PHP open tags ("<?php" / short echo "<?=") through their close tag.
        if ($char === '<' && $offset + 1 < $length && $content[$offset + 1] === '?') {
            $end = strpos($content, '?>', $offset + 2);
            $end = $end === false ? $length : $end + 2;

            return $end - $offset;
        }

        // Blade echoes ("{{ }}", "{!! !!}", "{{-- --}}") outside string literals.
        if (($echo = $this->echoSpan($content, $offset, $length)) !== null) {
            return $echo;
        }

        if ($char !== '@') {
            return null;
        }

        // Escaped "@@" is a literal "@", not a directive.
        if ($offset + 1 < $length && $content[$offset + 1] === '@') {
            return null;
        }

        // "@" preceded by a word character (e.g. an email) is not a directive.
        if ($offset > 0 && $this->isWord($content[$offset - 1])) {
            return null;
        }

        $cursor = $offset + 1;
        $name = '';

        while ($cursor < $length && $this->isWord($content[$cursor])) {
            $name .= $content[$cursor];
            $cursor++;
        }

        if ($name === '') {
            return null;
        }

        $lower = strtolower($name);

        if ($lower === 'php') {
            // "@php(...)" inline directive (immediate "(").
            if ($cursor < $length && $content[$cursor] === '(') {
                return $this->skipParens($content, $cursor, $length) - $offset;
            }

            // "@php ... @endphp" block.
            $end = stripos($content, '@endphp', $cursor);

            return $end === false ? null : ($end + strlen('@endphp')) - $offset;
        }

        if ($this->isBlockOpener($content, $lower, $cursor, $length)) {
            $end = $this->scanBlock($content, $offset, $length);

            return $end === null ? null : $end - $offset;
        }

        return null;
    }

    /**
     * Determine whether the given directive opens a maskable block.
     */
    private function isBlockOpener(string $content, string $lower, int $afterName, int $length): bool
    {
        if (in_array($lower, self::BLOCK_OPENERS, true)) {
            return true;
        }

        return $lower === 'empty' && $this->nextNonSpaceIsParen($content, $afterName, $length);
    }

    /**
     * Scan a directive block to its matching closer, returning the offset just past it.
     */
    private function scanBlock(string $content, int $start, int $length): ?int
    {
        $depth = 0;
        $offset = $start;

        while ($offset < $length) {
            $directive = $this->nextDirective($content, $offset, $length);

            if ($directive === null) {
                return null;
            }

            [$name, $afterName] = $directive;
            $lower = strtolower($name);

            if (str_starts_with($lower, 'end') && in_array($lower, self::BLOCK_CLOSERS, true)) {
                $depth--;

                if ($depth === 0) {
                    return $afterName;
                }

                $offset = $afterName;

                continue;
            }

            if ($this->isBlockOpener($content, $lower, $afterName, $length)) {
                $depth++;
            }

            // Skip any "(...)" argument list so its contents are never re-scanned.
            $offset = $this->skipOptionalParens($content, $afterName, $length);
        }

        return null;
    }

    /**
     * Find the next Blade directive at or after $from.
     *
     * @return array{0: string, 1: int}|null
     */
    private function nextDirective(string $content, int $from, int $length): ?array
    {
        $offset = $from;

        while ($offset < $length) {
            if ($content[$offset] !== '@') {
                $offset++;

                continue;
            }

            if ($offset + 1 < $length && $content[$offset + 1] === '@') {
                $offset += 2;

                continue;
            }

            if ($offset > 0 && $this->isWord($content[$offset - 1])) {
                $offset++;

                continue;
            }

            $cursor = $offset + 1;
            $name = '';

            while ($cursor < $length && $this->isWord($content[$cursor])) {
                $name .= $content[$cursor];
                $cursor++;
            }

            if ($name !== '') {
                return [$name, $cursor];
            }

            $offset++;
        }

        return null;
    }

    /**
     * Whether the next non-space character at or after $offset is "(".
     */
    private function nextNonSpaceIsParen(string $content, int $offset, int $length): bool
    {
        while ($offset < $length && ($content[$offset] === ' ' || $content[$offset] === "\t")) {
            $offset++;
        }

        return $offset < $length && $content[$offset] === '(';
    }

    /**
     * Skip an optional whitespace-then-"(...)" argument list.
     */
    private function skipOptionalParens(string $content, int $offset, int $length): int
    {
        $cursor = $offset;

        while ($cursor < $length && ($content[$cursor] === ' ' || $content[$cursor] === "\t")) {
            $cursor++;
        }

        if ($cursor < $length && $content[$cursor] === '(') {
            return $this->skipParens($content, $cursor, $length);
        }

        return $offset;
    }

    /**
     * Scan a balanced "(...)" group, returning the offset just past the matching ")".
     */
    private function skipParens(string $content, int $offset, int $length): int
    {
        $depth = 0;

        while ($offset < $length) {
            $char = $content[$offset];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                $offset++;

                if ($depth === 0) {
                    return $offset;
                }

                continue;
            }

            $offset++;
        }

        return $offset;
    }

    /**
     * Determine the CSS context (value or statement) at the given offset.
     */
    private function cssContext(string $content, int $offset): string
    {
        for ($cursor = $offset - 1; $cursor >= 0; $cursor--) {
            $char = $content[$cursor];

            if ($char === ':') {
                return 'value';
            }

            if ($char === ';' || $char === '{' || $char === '}') {
                return 'statement';
            }
        }

        return 'statement';
    }

    /**
     * Determine the JS context (expression or statement) at the given offset.
     */
    private function jsContext(string $content, int $offset): string
    {
        for ($cursor = $offset - 1; $cursor >= 0; $cursor--) {
            $char = $content[$cursor];

            if ($char === ' ' || $char === "\t") {
                continue;
            }

            if ($char === ';' || $char === '{' || $char === '}' || $char === "\n" || $char === "\r") {
                return 'js-statement';
            }

            return 'js-expression';
        }

        return 'js-statement';
    }

    /**
     * Build a unique, context-valid placeholder token.
     */
    private function makeToken(string $context): string
    {
        $index = $this->uniqueIndex();

        return match ($context) {
            'value', 'js-expression' => "__PINT_BLADE_{$index}__",
            'js-statement' => "__PINT_BLADE_{$index}__;",
            default => "--pint-blade-{$index}: 1;",
        };
    }

    /**
     * Pick the next index whose placeholder forms do not collide with the source.
     */
    private function uniqueIndex(): int
    {
        while (true) {
            $index = $this->counter++;

            $collides = str_contains($this->original, "__PINT_BLADE_{$index}__")
                || str_contains($this->original, "--pint-blade-{$index}");

            if (! $collides) {
                return $index;
            }
        }
    }

    /**
     * Determine whether a single byte is an identifier character.
     */
    private function isWord(string $char): bool
    {
        return $char === '_' || ctype_alnum($char);
    }

    /**
     * Whether a byte opens a string literal in the current region.
     */
    private function isStringDelimiter(string $char, bool $isCss): bool
    {
        if ($char === '"' || $char === "'") {
            return true;
        }

        return ! $isCss && $char === '`';
    }

    /**
     * Scan a string literal, returning the offset just past the closing quote.
     */
    private function scanStringLiteral(string $content, int $length, int $offset, string $quote): int
    {
        $cursor = $offset + 1;

        while ($cursor < $length) {
            $char = $content[$cursor];

            if ($char === '\\') {
                $cursor += 2;

                continue;
            }

            if (($echo = $this->echoSpan($content, $cursor, $length)) !== null) {
                $cursor += $echo;

                continue;
            }

            if ($char === $quote) {
                return $cursor + 1;
            }

            $cursor++;
        }

        return $length;
    }

    /**
     * The length of the Blade echo beginning at $offset, if any.
     */
    private function echoSpan(string $content, int $offset, int $length): ?int
    {
        if ($content[$offset] !== '{') {
            return null;
        }

        if (substr($content, $offset, 4) === '{{--') {
            $end = strpos($content, '--}}', $offset + 4);

            return $end === false ? null : ($end + 4) - $offset;
        }

        if (substr($content, $offset, 3) === '{!!') {
            $end = strpos($content, '!!}', $offset + 3);

            return $end === false ? null : ($end + 3) - $offset;
        }

        if (substr($content, $offset, 2) === '{{') {
            $end = strpos($content, '}}', $offset + 2);

            return $end === false ? null : ($end + 2) - $offset;
        }

        return null;
    }

    /**
     * Whether the given snippet contains a Blade echo.
     */
    private function containsEcho(string $value): bool
    {
        return str_contains($value, '{{') || str_contains($value, '{!!');
    }
}
