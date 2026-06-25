<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class DedentHuggedTerminator implements PrettierPostFormatter
{
    /** {@inheritDoc} */
    public function postFormat(string $content): string
    {
        $lines = explode("\n", $content);
        $lineCount = count($lines);

        $result = [];
        $index = 0;

        while ($index < $lineCount) {
            $line = $lines[$index];
            $isBareTag = preg_match('/^<([A-Za-z][A-Za-z0-9:_.\-]*)$/', trim($line), $matches) === 1;

            $terminator = $isBareTag
                ? $this->findHuggedTerminator($lines, $lineCount, $index + 1, $matches[1])
                : null;

            if ($terminator === null) {
                $result[] = $line;
                $index++;

                continue;
            }

            $indent = substr($line, 0, strlen($line) - strlen(ltrim($line)));

            $result[] = $line;

            for ($attribute = $index + 1; $attribute < $terminator; $attribute++) {
                $result[] = $lines[$attribute];
            }

            $result[] = $indent.ltrim($lines[$terminator]);
            $index = $terminator + 1;
        }

        return implode("\n", $result);
    }

    /**
     * Find the line that hugs the wrapped opening tag's terminator.
     *
     * @param  array<int, string>  $lines
     */
    private function findHuggedTerminator(array $lines, int $lineCount, int $start, string $tag): ?int
    {
        $closingTag = '</'.$tag.'>';

        for ($index = $start; $index < $lineCount; $index++) {
            $trimmed = trim($lines[$index]);

            if ($trimmed === '' || $trimmed[0] === '<') {
                return null;
            }

            if ($trimmed[0] === '>') {
                return str_ends_with($trimmed, $closingTag) ? $index : null;
            }
        }

        return null;
    }
}
