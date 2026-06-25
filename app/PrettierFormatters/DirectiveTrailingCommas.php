<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class DirectiveTrailingCommas implements PrettierPostFormatter
{
    /**
     * Control-structure directives whose top-level "(...)" is a condition, not a call.
     *
     * @var array<int, string>
     */
    private const CONTROL_DIRECTIVES = [
        'if', 'elseif', 'unless', 'while', 'for', 'foreach', 'forelse', 'switch', 'case',
    ];

    /**
     * PHP keywords followed by a "(...)" that is not a comma-safe call.
     *
     * @var array<int, string>
     */
    private const NON_CALL_KEYWORDS = [
        'if', 'elseif', 'else', 'while', 'for', 'foreach', 'switch', 'catch',
        'declare', 'do', 'match', 'empty',
    ];

    /** {@inheritDoc} */
    public function postFormat(string $content): string
    {
        $length = strlen($content);
        $offset = 0;

        /** @var array<int, int> $inserts byte offsets where a comma must be inserted */
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

            if ($char === '@') {
                $offset = $this->handleAt($content, $length, $offset, $inserts);

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
            $result .= substr($content, $cursor, $position - $cursor).',';
            $cursor = $position;
        }

        return $result.substr($content, $cursor);
    }

    /**
     * Handle a "@" encountered in the outer scan.
     *
     * @param  array<int, int>  $inserts
     */
    private function handleAt(string $content, int $length, int $offset, array &$inserts): int
    {
        $prev = $offset > 0 ? $content[$offset - 1] : '';

        if ($prev === '@' || $this->isWord($prev)) {
            return $offset + 1;
        }

        $cursor = $offset + 1;
        $name = '';

        while ($cursor < $length && $this->isWord($content[$cursor])) {
            $name .= $content[$cursor];
            $cursor++;
        }

        if ($name === '') {
            return $offset + 1;
        }

        $lower = strtolower($name);

        if ($lower === 'verbatim') {
            return $this->skipTo($content, '@endverbatim', $cursor) ?? $length;
        }

        if ($lower === 'php') {
            if ($cursor < $length && $content[$cursor] === '(') {
                return $this->scanDirectiveArgs($content, $length, $cursor, $lower, $inserts);
            }

            return $this->skipTo($content, '@endphp', $cursor) ?? $length;
        }

        $paren = $cursor;

        while ($paren < $length && ($content[$paren] === ' ' || $content[$paren] === "\t")) {
            $paren++;
        }

        if ($paren < $length && $content[$paren] === '(') {
            return $this->scanDirectiveArgs($content, $length, $paren, $lower, $inserts);
        }

        return $cursor;
    }

    /**
     * Scan a directive's "(...)" arguments, recording any required comma insertions.
     *
     * @param  array<int, int>  $inserts
     */
    private function scanDirectiveArgs(string $content, int $length, int $start, string $directive, array &$inserts): int
    {
        $stack = [
            $this->newFrame('(', $start, ! in_array($directive, self::CONTROL_DIRECTIVES, true)),
        ];

        $offset = $start + 1;

        while ($offset < $length && $stack !== []) {
            $char = $content[$offset];

            if ($char === "'" || $char === '"' || $char === '`') {
                $offset = $this->skipString($content, $length, $offset, $char);
                $this->mark($stack, $offset, $content[$offset - 1]);

                continue;
            }

            if ($char === '<' && substr($content, $offset, 3) === '<<<') {
                $start = $offset;
                $offset = $this->skipHeredoc($content, $length, $offset);
                $this->markNewlines($stack, $content, $start, $offset);
                $this->mark($stack, $offset, 'x');

                continue;
            }

            if ($char === '/' && $offset + 1 < $length && $content[$offset + 1] === '/') {
                $offset = $this->toEndOfLine($content, $length, $offset);

                continue;
            }

            if ($char === '#' && ! ($offset + 1 < $length && $content[$offset + 1] === '[')) {
                $offset = $this->toEndOfLine($content, $length, $offset);

                continue;
            }

            if ($char === '/' && $offset + 1 < $length && $content[$offset + 1] === '*') {
                $end = strpos($content, '*/', $offset + 2);
                $stop = $end === false ? $length : $end + 2;
                $this->markNewlines($stack, $content, $offset, $stop);
                $offset = $stop;

                continue;
            }

            if ($char === "\n") {
                foreach ($stack as $index => $_) {
                    $stack[$index]['multiline'] = true;
                }

                $offset++;

                continue;
            }

            if ($char === "\r" || $char === ' ' || $char === "\t") {
                $offset++;

                continue;
            }

            if ($char === '(' || $char === '[' || $char === '{') {
                $this->mark($stack, $offset + 1, $char);
                $isCall = $char === '(' && $this->parenIsCall($content, $offset);
                $stack[] = $this->newFrame($char, $offset, $isCall);
                $offset++;

                continue;
            }

            if ($char === ')' || $char === ']' || $char === '}') {
                $frame = array_pop($stack);
                $position = $this->decideInsert($content, $frame, $char, $offset);

                if ($position !== null) {
                    $inserts[] = $position;
                }

                if ($stack !== []) {
                    $this->mark($stack, $offset + 1, $char);
                }

                $offset++;

                continue;
            }

            $this->mark($stack, $offset + 1, $char);
            $offset++;
        }

        return $offset;
    }

    /**
     * Build a fresh bracket frame.
     *
     * @return array{open: string, openIndex: int, isCall: bool, multiline: bool, hasContent: bool, lastContentEnd: int, lastContentChar: string}
     */
    private function newFrame(string $open, int $openIndex, bool $isCall): array
    {
        return [
            'open' => $open,
            'openIndex' => $openIndex,
            'isCall' => $isCall,
            'multiline' => false,
            'hasContent' => false,
            'lastContentEnd' => $openIndex + 1,
            'lastContentChar' => '',
        ];
    }

    /**
     * Record a piece of meaningful content against the innermost frame.
     *
     * @param  array<int, array<string, mixed>>  $stack
     */
    private function mark(array &$stack, int $end, string $char): void
    {
        $top = count($stack) - 1;

        if ($top < 0) {
            return;
        }

        $stack[$top]['hasContent'] = true;
        $stack[$top]['lastContentEnd'] = $end;
        $stack[$top]['lastContentChar'] = $char;
    }

    /**
     * Mark every open frame as multiline when a range contains a newline.
     *
     * @param  array<int, array<string, mixed>>  $stack
     */
    private function markNewlines(array &$stack, string $content, int $from, int $to): void
    {
        if (strpos(substr($content, $from, $to - $from), "\n") === false) {
            return;
        }

        foreach ($stack as $index => $_) {
            $stack[$index]['multiline'] = true;
        }
    }

    /**
     * Decide whether (and where) a trailing comma should be inserted for a closed frame.
     *
     * @param  array{open: string, openIndex: int, isCall: bool, multiline: bool, hasContent: bool, lastContentEnd: int, lastContentChar: string}  $frame
     */
    private function decideInsert(string $content, array $frame, string $close, int $closeIndex): ?int
    {
        if (! $frame['multiline'] || ! $frame['hasContent']) {
            return null;
        }

        if ($frame['lastContentChar'] === ',') {
            return null;
        }

        if ($close === ']') {
            // Array literals always accept a trailing comma.
        } elseif ($close === ')') {
            if (! $frame['isCall']) {
                return null;
            }
        } else {
            return null;
        }

        if (! $this->closeOnOwnLine($content, $closeIndex)) {
            return null;
        }

        return $frame['lastContentEnd'];
    }

    /**
     * Determine whether the closing bracket sits on its own line.
     */
    private function closeOnOwnLine(string $content, int $index): bool
    {
        $cursor = $index - 1;

        while ($cursor >= 0) {
            $char = $content[$cursor];

            if ($char === "\n") {
                return true;
            }

            if ($char !== ' ' && $char !== "\t" && $char !== "\r") {
                return false;
            }

            $cursor--;
        }

        return true;
    }

    /**
     * Determine whether a "(" begins a call argument list rather than a grouping or condition.
     */
    private function parenIsCall(string $content, int $parenIndex): bool
    {
        $cursor = $parenIndex - 1;

        while ($cursor >= 0 && ($content[$cursor] === ' ' || $content[$cursor] === "\t")) {
            $cursor--;
        }

        if ($cursor < 0) {
            return false;
        }

        $char = $content[$cursor];

        if ($char === ']' || $char === ')') {
            return true;
        }

        if ($this->isWord($char)) {
            $end = $cursor;

            while ($cursor >= 0 && $this->isWord($content[$cursor])) {
                $cursor--;
            }

            $identifier = strtolower(substr($content, $cursor + 1, $end - $cursor));

            return ! in_array($identifier, self::NON_CALL_KEYWORDS, true);
        }

        return false;
    }

    /**
     * Skip a quoted string or backtick expression.
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
     * Skip a heredoc/nowdoc, returning the offset just past the closing label.
     */
    private function skipHeredoc(string $content, int $length, int $offset): int
    {
        $cursor = $offset + 3;

        while ($cursor < $length && ($content[$cursor] === ' ' || $content[$cursor] === "\t")) {
            $cursor++;
        }

        if ($cursor < $length && ($content[$cursor] === '"' || $content[$cursor] === "'")) {
            $cursor++;
        }

        $label = '';

        while ($cursor < $length && $this->isWord($content[$cursor])) {
            $label .= $content[$cursor];
            $cursor++;
        }

        if ($label === '') {
            return $offset + 3;
        }

        $search = $cursor;

        while (true) {
            $newline = strpos($content, "\n", $search);

            if ($newline === false) {
                return $length;
            }

            $lineStart = $newline + 1;
            $probe = $lineStart;

            while ($probe < $length && ($content[$probe] === ' ' || $content[$probe] === "\t")) {
                $probe++;
            }

            if (substr($content, $probe, strlen($label)) === $label) {
                $after = $probe + strlen($label);
                $next = $after < $length ? $content[$after] : "\n";

                if (! $this->isWord($next)) {
                    return $after;
                }
            }

            $search = $lineStart;
        }
    }

    /**
     * Return the offset of the next newline, without consuming it.
     */
    private function toEndOfLine(string $content, int $length, int $offset): int
    {
        $newline = strpos($content, "\n", $offset);

        return $newline === false ? $length : $newline;
    }

    /**
     * Find the offset just past a given directive token, searching from $from.
     */
    private function skipTo(string $content, string $directive, int $from): ?int
    {
        $position = stripos($content, $directive, $from);

        return $position === false ? null : $position + strlen($directive);
    }

    /**
     * Determine whether a single byte is an identifier character.
     */
    private function isWord(string $char): bool
    {
        return $char === '_' || ctype_alnum($char);
    }
}
