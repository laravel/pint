<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class JoinDanglingCloseBracket implements PrettierPostFormatter
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
            $nextLine = $lines[$index + 1] ?? null;

            $danglesCloseBracket = $nextLine !== null
                && trim($nextLine) === '>'
                && preg_match('/<\/[A-Za-z][A-Za-z0-9:_.\-]*$/', rtrim($line)) === 1;

            if ($danglesCloseBracket) {
                $result[] = rtrim($line).'>';
                $index += 2;

                continue;
            }

            $result[] = $line;
            $index++;
        }

        return implode("\n", $result);
    }
}
