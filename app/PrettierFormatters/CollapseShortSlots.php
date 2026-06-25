<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class CollapseShortSlots implements PrettierPostFormatter
{
    /**
     * The maximum line length, mirroring "printWidth" in the bundled prettier configuration.
     */
    private const PRINT_WIDTH = 120;

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
     * Attempt to collapse a slot whose opening tag is on the given line.
     *
     * @param  array<int, string>  $lines
     * @return array{string, int}|null
     */
    private function tryCollapse(array $lines, int $lineCount, int $index): ?array
    {
        $line = $lines[$index];
        $trimmed = ltrim($line);

        if (! $this->opensSlot($trimmed)) {
            return null;
        }

        $indent = substr($line, 0, strlen($line) - strlen($trimmed));

        $tagEnd = $this->openTagEnd($trimmed);

        if ($tagEnd === null) {
            return null;
        }

        if ($trimmed[$tagEnd - 1] === '/') {
            return null;
        }

        if (rtrim(substr($trimmed, $tagEnd + 1)) !== '') {
            return null;
        }

        if ($index + 2 >= $lineCount) {
            return null;
        }

        $body = trim($lines[$index + 1]);
        $closingTag = ltrim($lines[$index + 2]);

        if (! $this->closesSlot($closingTag)) {
            return null;
        }

        if (! $this->bodyQualifies($body)) {
            return null;
        }

        $collapsed = $indent.$trimmed.$body.rtrim($closingTag);

        if (strlen($collapsed) > self::PRINT_WIDTH) {
            return null;
        }

        return [$collapsed, $index + 3];
    }

    /**
     * Whether a trimmed line begins a slot opening tag.
     */
    private function opensSlot(string $trimmed): bool
    {
        if (! str_starts_with($trimmed, '<x-slot')) {
            return false;
        }

        $rest = substr($trimmed, 7);

        return $rest === '' || $rest[0] === '>' || $rest[0] === ':' || $rest[0] === ' ' || $rest[0] === "\t";
    }

    /**
     * Whether a trimmed line is a slot closing tag.
     */
    private function closesSlot(string $trimmed): bool
    {
        return preg_match('/^<\/x-slot[:>]/', $trimmed) === 1;
    }

    /**
     * The index of the ">" that ends the opening tag, or null when it does not close on this line.
     */
    private function openTagEnd(string $trimmed): ?int
    {
        $length = strlen($trimmed);
        $offset = 0;

        while ($offset < $length) {
            $char = $trimmed[$offset];

            if ($char === '"' || $char === "'") {
                $end = strpos($trimmed, $char, $offset + 1);

                if ($end === false) {
                    return null;
                }

                $offset = $end + 1;

                continue;
            }

            if ($char === '>') {
                return $offset;
            }

            $offset++;
        }

        return null;
    }

    /**
     * Whether a slot body is a single, collapsible line.
     */
    private function bodyQualifies(string $body): bool
    {
        if ($body === '') {
            return false;
        }

        return ! str_contains($body, '<') && ! str_contains($body, '@');
    }
}
