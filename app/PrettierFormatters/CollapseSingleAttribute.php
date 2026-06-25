<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class CollapseSingleAttribute implements PrettierPostFormatter
{
    /**
     * {@inheritDoc}
     */
    public function postFormat(string $content): string
    {
        $lines = explode("\n", $content);
        $lineCount = count($lines);

        $result = [];
        $index = 0;

        while ($index < $lineCount) {
            $collapsed = $this->tryCollapse($lines, $lineCount, $index);

            if ($collapsed !== null) {
                [$line, $nextIndex] = $collapsed;
                $result[] = $line;
                $index = $nextIndex;

                continue;
            }

            $result[] = $lines[$index];
            $index++;
        }

        return implode("\n", $result);
    }

    /**
     * Attempt to collapse the wrapped opening tag that begins on line $index.
     *
     * @param  array<int, string>  $lines
     * @return array{string, int}|null
     */
    private function tryCollapse(array $lines, int $lineCount, int $index): ?array
    {
        $line = $lines[$index];
        $trimmed = ltrim($line);
        $indent = substr($line, 0, strlen($line) - strlen($trimmed));

        if (preg_match('/^<([A-Za-z][A-Za-z0-9:_.\-]*)\s+(\S.*)$/', $trimmed, $matches) === 1) {
            $tag = $matches[1];
            $attribute = rtrim($matches[2]);

            if ($this->attributeCount($attribute) !== 1) {
                return null;
            }

            $hugged = $this->extractHuggedContent($lines, $lineCount, $index + 1, $tag);

            if ($hugged === null) {
                return null;
            }

            [$body, $nextIndex] = $hugged;

            return [$indent.'<'.$tag.' '.$attribute.'>'.$body.'</'.$tag.'>', $nextIndex];
        }

        if (preg_match('/^<[A-Za-z][A-Za-z0-9:_.\-]*$/', $trimmed) !== 1) {
            return null;
        }

        $tag = substr($trimmed, 1);

        $wrapped = $this->tryCollapseWrappedAttribute($lines, $lineCount, $index, $indent, $tag);

        if ($wrapped !== null) {
            return $wrapped;
        }

        if ($index + 2 >= $lineCount) {
            return null;
        }

        $attribute = trim($lines[$index + 1]);

        if ($attribute === '' || $attribute === '>' || $attribute === '/>') {
            return null;
        }

        if ($this->attributeCount($attribute) !== 1) {
            return null;
        }

        $terminator = trim($lines[$index + 2]);

        if ($terminator === '>' || $terminator === '/>') {
            $suffix = $terminator === '/>' ? ' />' : '>';

            return [$indent.$trimmed.' '.$attribute.$suffix, $index + 3];
        }

        $hugged = $this->extractHuggedContent($lines, $lineCount, $index + 2, $tag);

        if ($hugged === null) {
            return null;
        }

        [$body, $nextIndex] = $hugged;

        return [$indent.'<'.$tag.' '.$attribute.'>'.$body.'</'.$tag.'>', $nextIndex];
    }

    /**
     * Attempt to collapse a tag whose single attribute is a wrapped Blade construct.
     *
     * @param  array<int, string>  $lines
     * @return array{string, int}|null
     */
    private function tryCollapseWrappedAttribute(array $lines, int $lineCount, int $index, string $indent, string $tag): ?array
    {
        $start = $index + 1;

        if ($start >= $lineCount) {
            return null;
        }

        $firstLine = $lines[$start];
        $trimmedFirst = ltrim($firstLine);

        if (! $this->opensBladeAttribute($trimmedFirst)) {
            return null;
        }

        $dedent = strlen($firstLine) - strlen($trimmedFirst) - strlen($indent);

        if ($dedent <= 0) {
            return null;
        }

        $end = $this->wrappedAttributeEnd($lines, $lineCount, $start);

        if ($end === null || $end === $start) {
            return null;
        }

        if ($end + 1 >= $lineCount) {
            return null;
        }

        $terminator = trim($lines[$end + 1]);

        if ($terminator !== '>' && $terminator !== '/>') {
            return null;
        }

        $suffix = $terminator === '/>' ? ' />' : '>';

        $result = [$indent.'<'.$tag.' '.rtrim($trimmedFirst)];

        for ($continuation = $start + 1; $continuation <= $end; $continuation++) {
            $line = $this->dedent($lines[$continuation], $dedent);

            if ($continuation === $end) {
                $line = rtrim($line).$suffix;
            }

            $result[] = $line;
        }

        return [implode("\n", $result), $end + 2];
    }

    /**
     * Determine whether a trimmed line opens a Blade construct usable as an attribute.
     */
    private function opensBladeAttribute(string $trimmed): bool
    {
        return str_starts_with($trimmed, '@')
            || str_starts_with($trimmed, '{{')
            || str_starts_with($trimmed, '{!!');
    }

    /**
     * Find the line on which a wrapped Blade construct beginning at $start closes.
     *
     * @param  array<int, string>  $lines
     */
    private function wrappedAttributeEnd(array $lines, int $lineCount, int $start): ?int
    {
        $depth = 0;
        $quote = null;
        $opened = false;

        for ($index = $start; $index < $lineCount; $index++) {
            $line = $lines[$index];
            $length = strlen($line);

            for ($offset = 0; $offset < $length; $offset++) {
                $char = $line[$offset];

                if ($quote !== null) {
                    if ($char === $quote) {
                        $quote = null;
                    }

                    continue;
                }

                if ($char === '"' || $char === "'") {
                    $quote = $char;

                    continue;
                }

                if ($char === '(' || $char === '[' || $char === '{') {
                    $depth++;
                    $opened = true;

                    continue;
                }

                if (($char === ')' || $char === ']' || $char === '}') && --$depth < 0) {
                    return null;
                }
            }

            if ($opened && $depth === 0) {
                return $index;
            }

            if ($quote !== null) {
                return null;
            }
        }

        return null;
    }

    /**
     * Remove up to $amount leading spaces from a line.
     */
    private function dedent(string $line, int $amount): string
    {
        $strip = 0;
        $length = strlen($line);

        while ($strip < $amount && $strip < $length && $line[$strip] === ' ') {
            $strip++;
        }

        return substr($line, $strip);
    }

    /**
     * Extract the inner content of an element whose content hugs its tags.
     *
     * @param  array<int, string>  $lines
     * @return array{string, int}|null
     */
    private function extractHuggedContent(array $lines, int $lineCount, int $start, string $tag): ?array
    {
        if ($start >= $lineCount) {
            return null;
        }

        $trimmed = trim($lines[$start]);

        if ($trimmed === '' || $trimmed[0] !== '>') {
            return null;
        }

        $closingTag = '</'.$tag;

        if (str_ends_with($trimmed, $closingTag.'>')) {
            $body = substr($trimmed, 1, strlen($trimmed) - 1 - strlen($closingTag) - 1);

            return [$body, $start + 1];
        }

        if (str_ends_with($trimmed, $closingTag)) {
            if ($start + 1 >= $lineCount || trim($lines[$start + 1]) !== '>') {
                return null;
            }

            $body = substr($trimmed, 1, strlen($trimmed) - 1 - strlen($closingTag));

            return [$body, $start + 2];
        }

        if ($start + 1 < $lineCount && trim($lines[$start + 1]) === $closingTag.'>') {
            return [substr($trimmed, 1), $start + 2];
        }

        return null;
    }

    /**
     * Count the top-level attributes in the text following a tag name.
     */
    private function attributeCount(string $attributes): int
    {
        $length = strlen($attributes);
        $total = 0;
        $inToken = false;
        $depth = 0;
        $quote = null;

        for ($offset = 0; $offset < $length; $offset++) {
            $char = $attributes[$offset];

            if ($quote !== null) {
                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $this->beginToken($total, $inToken, $depth);
                $quote = $char;

                continue;
            }

            if ($char === '(' || $char === '[' || $char === '{') {
                $this->beginToken($total, $inToken, $depth);
                $depth++;

                continue;
            }

            if ($char === ')' || $char === ']' || $char === '}') {
                if (--$depth < 0) {
                    return -1;
                }

                continue;
            }

            if ($depth === 0) {
                // A bare ">" closes the start tag, so it was never wrapped.
                if ($char === '>') {
                    return -1;
                }

                if (ctype_space($char)) {
                    $inToken = false;

                    continue;
                }
            }

            $this->beginToken($total, $inToken, $depth);
        }

        if ($quote !== null || $depth !== 0) {
            return -1;
        }

        return $total;
    }

    /**
     * Mark the start of a new top-level attribute token.
     */
    private function beginToken(int &$total, bool &$inToken, int $depth): void
    {
        if (! $inToken && $depth === 0) {
            $total++;
        }

        $inToken = true;
    }
}
