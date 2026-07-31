<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;
use App\Exceptions\UnrestorableContentException;

class AlpineMaskPatterns implements PrettierPostFormatter, PrettierPreFormatter
{
    /**
     * The "x-mask" attributes whose value is a literal pattern.
     *
     * A trailing ".modifier" still masks a pattern, but "x-mask:dynamic" holds a real
     * JS expression and is deliberately left for prettier to format. The value may be
     * double quoted, single quoted, or - since HTML allows it - unquoted.
     *
     * @var string
     */
    private const PATTERN = '/(?<![-\w:.@])x-mask(?:\.[\w.-]+)?\s*=\s*(?:(["\'])(.+?)\1|([^\s"\'=<>`]+))/i';

    /**
     * The placeholder to original-value map.
     *
     * @var array<string, string>
     */
    private array $map = [];

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
        $this->original = $content;
        $this->counter = 0;

        // Alpine reads an "x-mask" value as a literal template, but prettier formats it as a
        // JS expression: a pattern such as "9999-999" parses as a subtraction and comes back
        // as "9999 - 999", which breaks the mask. Mask the value so prettier cannot see it.
        return (string) preg_replace_callback(
            self::PATTERN,
            function (array $matches): string {
                [$whole, $wholeOffset] = $matches[0];
                [$value, $valueOffset] = ($matches[3][1] ?? -1) !== -1 ? $matches[3] : $matches[2];

                $token = $this->mask($value);

                // Splice the token in at the value's exact position within the match, so a value
                // that also appears inside the attribute name (e.g. "a" in "x-mask") is left alone.
                $position = $valueOffset - $wholeOffset;

                return substr($whole, 0, $position)
                    .$token
                    .substr($whole, $position + strlen($value));
            },
            $content,
            flags: PREG_OFFSET_CAPTURE,
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws UnrestorableContentException
     */
    public function postFormat(string $content): string
    {
        if ($this->map === []) {
            return $content;
        }

        // Every token must come back exactly once. When one does not, restoring would
        // emit a half-masked file, so bail out and let the whole run be discarded.
        foreach (array_keys($this->map) as $token) {
            if (substr_count($content, $token) !== 1) {
                throw new UnrestorableContentException;
            }
        }

        return str_replace(array_keys($this->map), array_values($this->map), $content);
    }

    /**
     * Record the value under a fresh placeholder token and return that token.
     */
    private function mask(string $value): string
    {
        $token = $this->makeToken(strlen($value));

        $this->map[$token] = $value;

        return $token;
    }

    /**
     * Build a unique placeholder token that prettier leaves untouched.
     *
     * The token is a plain identifier, so prettier prints it back byte for byte wherever
     * the pattern used to sit, and it is padded to the pattern's own byte length so the
     * tag keeps its original width and prettier makes the same wrapping decisions it
     * would have made for the unmasked source.
     *
     * The index always ends on a "_", which keeps a shorter token from hiding inside a
     * longer one ("pm1_" can never be a slice of "pm12_"), and the token widens until the
     * source no longer contains it.
     */
    private function makeToken(int $length): string
    {
        $token = 'pm'.$this->counter++.'_';

        if (strlen($token) < $length) {
            $token = str_pad($token, $length, '_');
        }

        while (str_contains($this->original, $token)) {
            $token .= '_';
        }

        return $token;
    }
}
