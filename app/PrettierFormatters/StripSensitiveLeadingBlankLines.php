<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class StripSensitiveLeadingBlankLines implements PrettierPostFormatter
{
    /**
     * The whitespace-sensitive elements whose inner whitespace is significant.
     *
     * @var array<int, string>
     */
    private const SENSITIVE = ['pre', 'textarea'];

    /** {@inheritDoc} */
    public function postFormat(string $content): string
    {
        $lines = explode("\n", $content);
        $lineCount = count($lines);

        $result = [];
        $index = 0;

        while ($index < $lineCount) {
            $result[] = $lines[$index];

            if ($this->opensInlineSensitiveTag($lines[$index])) {
                $index++;

                while ($index < $lineCount && trim($lines[$index]) === '') {
                    $index++;
                }

                continue;
            }

            $terminator = $this->opensWrappedSensitiveTag($lines, $lineCount, $index);

            if ($terminator === null) {
                $index++;

                continue;
            }

            for ($tagLine = $index + 1; $tagLine <= $terminator; $tagLine++) {
                $result[] = $lines[$tagLine];
            }

            $index = $terminator + 1;

            while ($index < $lineCount && trim($lines[$index]) === '') {
                $index++;
            }
        }

        return implode("\n", $result);
    }

    /**
     * Determine if the line is a single-line opening tag for a sensitive element carrying a directive.
     */
    private function opensInlineSensitiveTag(string $line): bool
    {
        $trimmed = trim($line);

        if (preg_match('/^<(pre|textarea)\b/i', $trimmed) !== 1) {
            return false;
        }

        $end = $this->openTagEnd($trimmed);

        if ($end === null || $end !== strlen($trimmed) - 1 || ($end > 0 && $trimmed[$end - 1] === '/')) {
            return false;
        }

        // A "@name" directive (not "@@" escaped, nor an "@" glued to a word).
        return preg_match('/(?<![\w@])@[a-zA-Z]/', $trimmed) === 1;
    }

    /**
     * Get the index of the ">" that closes the opening tag, or null when it does not close on this line.
     */
    private function openTagEnd(string $trimmed): ?int
    {
        $length = strlen($trimmed);
        $offset = 0;
        $depth = 0;

        while ($offset < $length) {
            $char = $trimmed[$offset];

            if ($char === '"' || $char === "'") {
                $close = strpos($trimmed, $char, $offset + 1);

                if ($close === false) {
                    return null;
                }

                $offset = $close + 1;

                continue;
            }

            // A ">" inside a directive's argument list — most commonly the ">"
            // of a "=>" inside "@class([...])" — is not the tag terminator.
            if ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                $depth = max(0, $depth - 1);
            } elseif ($char === '>' && $depth === 0) {
                return $offset;
            }

            $offset++;
        }

        return null;
    }

    /**
     * Get the index of the line that terminates a wrapped sensitive opening tag, or null when there is none.
     *
     * @param  array<int, string>  $lines
     */
    private function opensWrappedSensitiveTag(array $lines, int $lineCount, int $index): ?int
    {
        if (preg_match('/^<([A-Za-z][A-Za-z0-9]*)$/', trim($lines[$index]), $matches) !== 1) {
            return null;
        }

        if (! in_array(strtolower($matches[1]), self::SENSITIVE, true)) {
            return null;
        }

        for ($tagLine = $index + 1; $tagLine < $lineCount; $tagLine++) {
            $trimmed = trim($lines[$tagLine]);

            if ($trimmed === '>') {
                return $tagLine;
            }

            if ($trimmed === '/>') {
                return null;
            }
        }

        return null;
    }
}
