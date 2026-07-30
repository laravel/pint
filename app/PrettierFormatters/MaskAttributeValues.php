<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;

class MaskAttributeValues implements PrettierPostFormatter, PrettierPreFormatter
{
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

        // Alpine reads "x-mask" as a literal template, but prettier formats it as a JS
        // expression and spaces out the hyphen in patterns like "9999-999". "x-mask:dynamic"
        // holds a real expression and is left alone.
        return (string) preg_replace_callback(
            '/(?<![-\w:])x-mask\s*=\s*(["\'])(.+?)\1/i',
            function (array $matches): string {
                $token = $this->mask($matches[2]);

                return str_replace($matches[2], $token, $matches[0]);
            },
            $content,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function postFormat(string $content): string
    {
        if ($this->map === []) {
            return $content;
        }

        // Every token must appear exactly once; otherwise fall back to the original to avoid corrupting the file.
        foreach (array_keys($this->map) as $token) {
            if (substr_count($content, $token) !== 1) {
                return $this->original;
            }
        }

        return str_replace(array_keys($this->map), array_values($this->map), $content);
    }

    /**
     * Record the value under a fresh placeholder token and return that token.
     */
    private function mask(string $value): string
    {
        $token = $this->makeToken();

        $this->map[$token] = $value;

        return $token;
    }

    /**
     * Build a unique placeholder token that prettier leaves untouched.
     */
    private function makeToken(): string
    {
        while (true) {
            $token = "__PINT_MASK_{$this->counter}__";
            $this->counter++;

            if (! str_contains($this->original, $token)) {
                return $token;
            }
        }
    }
}
