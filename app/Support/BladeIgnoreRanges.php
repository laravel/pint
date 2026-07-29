<?php

namespace App\Support;

class BladeIgnoreRanges
{
    /**
     * The placeholder to original-text map.
     *
     * @var array<string, string>
     */
    private array $map = [];

    /**
     * The content as it entered protect().
     */
    private string $original = '';

    /**
     * The index used to build unique placeholder tokens.
     */
    private int $counter = 0;

    /**
     * Protect the given formatter ignore ranges.
     *
     * @param  array<int, array{start: int, end: int, sourceStart?: int, sourceEnd?: int}>  $ranges
     */
    public function protect(string $content, array $ranges, string $source): string
    {
        $this->map = [];
        $this->original = $content;
        $this->counter = 0;

        if ($ranges === []) {
            return $content;
        }

        $result = '';
        $cursor = 0;

        foreach ($ranges as $range) {
            $token = $this->makeToken();
            $sourceStart = $range['sourceStart'] ?? $range['start'];
            $sourceEnd = $range['sourceEnd'] ?? $range['end'];

            $this->map[$token] = substr($source, $sourceStart, $sourceEnd - $sourceStart);
            $result .= substr($content, $cursor, $range['start'] - $cursor).$token;
            $cursor = $range['end'];
        }

        return $result.substr($content, $cursor);
    }

    /**
     * Restore the contents of formatter ignore ranges.
     */
    public function restore(string $content): string
    {
        if ($this->map === []) {
            $this->original = '';

            return $content;
        }

        $map = $this->map;
        $original = $this->original;

        $this->map = [];
        $this->original = '';

        // Every token must appear exactly once; otherwise fall back to the original to avoid corrupting the file.
        foreach (array_keys($map) as $token) {
            if (substr_count($content, $token) !== 1) {
                return $original;
            }
        }

        return str_replace(array_keys($map), array_values($map), $content);
    }

    /**
     * Build a unique placeholder token.
     */
    private function makeToken(): string
    {
        while (true) {
            $token = sprintf('__PINT_BLADE_IGNORE_%d__', $this->counter++);

            if (! str_contains($this->original, $token)) {
                return $token;
            }
        }
    }
}
