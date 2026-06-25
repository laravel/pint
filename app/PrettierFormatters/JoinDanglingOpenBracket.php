<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

class JoinDanglingOpenBracket implements PrettierPostFormatter
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

            if ($isBareTag && $index + 1 < $lineCount) {
                $tag = $matches[1];
                $nextLine = ltrim($lines[$index + 1]);

                if ($nextLine !== '' && $nextLine[0] === '>' && str_ends_with($nextLine, '</'.$tag.'>')) {
                    $result[] = rtrim($line).$nextLine;
                    $index += 2;

                    continue;
                }
            }

            $result[] = $line;
            $index++;
        }

        return implode("\n", $result);
    }
}
