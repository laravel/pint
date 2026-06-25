<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class NotOperatorSpacing implements PrettierPostFormatter
{
    /**
     * Attribute name prefixes whose values hold JS or PHP expressions.
     *
     * @var array<int, string>
     */
    private const JS_ATTRIBUTE_PREFIXES = ['x-', '@', ':'];

    /**
     * Raw-text elements whose bodies are never HTML.
     *
     * @var array<int, string>
     */
    private const RAW_TEXT_ELEMENTS = ['script', 'style'];

    /** {@inheritDoc} */
    public function postFormat(string $content): string
    {
        $length = strlen($content);
        $offset = 0;

        /** @var array<int, int> $inserts */
        $inserts = [];

        while ($offset < $length) {
            $char = $content[$offset];

            if ($char === '<' && $offset + 1 < $length && $content[$offset + 1] === '?') {
                $end = strpos($content, '?>', $offset + 2);
                $offset = $end === false ? $length : $end + 2;

                continue;
            }

            if ($char === '{' && substr($content, $offset, 4) === '{{--') {
                $end = strpos($content, '--}}', $offset + 4);
                $offset = $end === false ? $length : $end + 4;

                continue;
            }

            if ($char === '{' && substr($content, $offset, 3) === '{!!') {
                $end = strpos($content, '!!}', $offset + 3);
                $offset = $end === false ? $length : $end + 3;

                continue;
            }

            if ($char === '{' && substr($content, $offset, 2) === '{{') {
                $end = strpos($content, '}}', $offset + 2);
                $offset = $end === false ? $length : $end + 2;

                continue;
            }

            if ($char === '<' && $offset + 1 < $length && $this->startsTag($content[$offset + 1])) {
                $offset = $this->handleTag($content, $length, $offset, $inserts);

                continue;
            }

            $offset++;
        }

        if ($inserts === []) {
            return $content;
        }

        sort($inserts);

        $result = '';
        $cursor = 0;

        foreach ($inserts as $position) {
            $result .= substr($content, $cursor, $position - $cursor).' ';
            $cursor = $position;
        }

        return $result.substr($content, $cursor);
    }

    /**
     * Parse a single tag starting at the "<".
     *
     * @param  array<int, int>  $inserts
     */
    private function handleTag(string $content, int $length, int $offset, array &$inserts): int
    {
        $cursor = $offset + 1;

        $isClosing = $cursor < $length && $content[$cursor] === '/';

        if ($isClosing) {
            $cursor++;
        }

        $name = '';

        while ($cursor < $length && $this->isNameChar($content[$cursor])) {
            $name .= $content[$cursor];
            $cursor++;
        }

        while ($cursor < $length) {
            $char = $content[$cursor];

            if ($char === '>') {
                $cursor++;

                break;
            }

            if ($char === '/') {
                $cursor++;

                continue;
            }

            if ($char === '{' && substr($content, $cursor, 2) === '{{') {
                $end = strpos($content, '}}', $cursor + 2);
                $cursor = $end === false ? $length : $end + 2;

                continue;
            }

            // A directive's "(...)" may contain ">", so consume it as a balanced group.
            if ($char === '@') {
                $cursor = $this->handleInTagDirective($content, $length, $cursor, $inserts);

                continue;
            }

            if ($this->isNameStart($char)) {
                $cursor = $this->handleAttribute($content, $length, $cursor, $inserts);

                continue;
            }

            $cursor++;
        }

        if (! $isClosing && in_array(strtolower($name), self::RAW_TEXT_ELEMENTS, true)) {
            // Guard the offset: a malformed tag can leave the cursor past the content end.
            $close = $cursor < $length ? stripos($content, '</'.$name, $cursor) : false;

            return $close === false ? $length : $close;
        }

        return $cursor;
    }

    /**
     * Handle an attribute, scanning its value for "!" when JS-bearing.
     *
     * @param  array<int, int>  $inserts
     */
    private function handleAttribute(string $content, int $length, int $cursor, array &$inserts): int
    {
        $name = '';

        while ($cursor < $length && $this->isNameChar($content[$cursor])) {
            $name .= $content[$cursor];
            $cursor++;
        }

        while ($cursor < $length && $this->isSpace($content[$cursor])) {
            $cursor++;
        }

        if ($cursor >= $length || $content[$cursor] !== '=') {
            return $cursor;
        }

        $cursor++;

        while ($cursor < $length && $this->isSpace($content[$cursor])) {
            $cursor++;
        }

        if ($cursor >= $length) {
            return $cursor;
        }

        $quote = $content[$cursor];

        // Only double-quoted values are scanned: prettier emits JS attributes
        // double-quoted and escapes any inner '"', so the value holds no raw '"'.
        if ($quote !== '"') {
            if ($quote === "'") {
                $end = strpos($content, "'", $cursor + 1);

                return $end === false ? $length : $end + 1;
            }

            while ($cursor < $length && ! $this->isSpace($content[$cursor]) && $content[$cursor] !== '>') {
                $cursor++;
            }

            return $cursor;
        }

        $valueStart = $cursor + 1;
        $valueEnd = strpos($content, '"', $valueStart);
        $valueEnd = $valueEnd === false ? $length : $valueEnd;

        if ($this->isJsAttribute($name)) {
            $this->scanValue($content, $valueStart, $valueEnd, $inserts);
        }

        return min($valueEnd + 1, $length);
    }

    /**
     * Consume a Blade directive that appears inside a tag.
     *
     * @param  array<int, int>  $inserts
     */
    private function handleInTagDirective(string $content, int $length, int $cursor, array &$inserts): int
    {
        $probe = $cursor + 1;

        while ($probe < $length && $this->isNameChar($content[$probe])) {
            $probe++;
        }

        $after = $probe;

        while ($after < $length && $this->isSpace($content[$after])) {
            $after++;
        }

        if ($after < $length && $content[$after] === '=') {
            return $this->handleAttribute($content, $length, $cursor, $inserts);
        }

        if ($after < $length && $content[$after] === '(') {
            return $this->skipBalancedParens($content, $length, $after);
        }

        return $probe;
    }

    /**
     * Skip a balanced "(...)" group beginning at $cursor, honouring quoted strings.
     */
    private function skipBalancedParens(string $content, int $length, int $cursor): int
    {
        $depth = 0;

        while ($cursor < $length) {
            $char = $content[$cursor];

            if ($char === "'" || $char === '"' || $char === '`') {
                $cursor = $this->skipString($content, $length, $cursor, $char);

                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return $cursor + 1;
                }
            }

            $cursor++;
        }

        return $cursor;
    }

    /**
     * Scan a JS attribute value, recording the offset after each unary "!".
     *
     * @param  array<int, int>  $inserts
     */
    private function scanValue(string $content, int $start, int $end, array &$inserts): void
    {
        $offset = $start;

        while ($offset < $end) {
            $char = $content[$offset];

            // A "!" inside a JS string literal is text, not an operator.
            if ($char === "'" || $char === '`') {
                $offset = min($this->skipString($content, $end, $offset, $char), $end);

                continue;
            }

            if ($char === '{' && substr($content, $offset, 2) === '{{') {
                $close = strpos($content, '}}', $offset + 2);
                $offset = $close === false || $close >= $end ? $end : $close + 2;

                continue;
            }

            if ($char !== '!') {
                $offset++;

                continue;
            }

            $next = $offset + 1 < $end ? $content[$offset + 1] : '';

            // "!=" is a comparison, "!!" double negation, and a space is already correct.
            if ($next === '=' || $next === '!' || $next === '' || $this->isSpace($next)) {
                $offset += $next === '!' ? 2 : 1;

                continue;
            }

            $inserts[] = $offset + 1;
            $offset++;
        }
    }

    /**
     * Skip a quoted string, returning the offset just past the closing delimiter.
     */
    private function skipString(string $content, int $length, int $offset, string $quote): int
    {
        $offset++;

        while ($offset < $length) {
            $char = $content[$offset];

            if ($char === '\\') {
                $offset += 2;

                continue;
            }

            if ($char === $quote) {
                return $offset + 1;
            }

            $offset++;
        }

        return $length;
    }

    /**
     * Whether the attribute name denotes a JS/PHP expression value.
     */
    private function isJsAttribute(string $name): bool
    {
        foreach (self::JS_ATTRIBUTE_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a byte may follow "<" to begin a tag (a letter or a closing slash).
     */
    private function startsTag(string $char): bool
    {
        return $char === '/' || ctype_alpha($char);
    }

    /**
     * Whether a byte can start an attribute name.
     */
    private function isNameStart(string $char): bool
    {
        return ctype_alpha($char) || $char === '_' || $char === ':' || $char === '@';
    }

    /**
     * Whether a byte is part of a tag or attribute name.
     */
    private function isNameChar(string $char): bool
    {
        return ctype_alnum($char) || $char === '-' || $char === '_' || $char === ':' || $char === '.' || $char === '@';
    }

    /**
     * Whether a byte is insignificant whitespace.
     */
    private function isSpace(string $char): bool
    {
        return $char === ' ' || $char === "\t" || $char === "\n" || $char === "\r";
    }
}
