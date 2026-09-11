<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;

/**
 * Prettier's HTML printer terminates every void element with the XHTML style
 * "/", turning "<br>" into "<br />". Projects that require plain HTML output
 * may opt out of that by setting "blade.void_element_slash" to false in their
 * "pint.json", which turns this pass on. It is off by default, so prettier's
 * own output is what Pint prints unless the option asks otherwise.
 */
class StripVoidElementSlash implements PrettierPostFormatter
{
    /**
     * The HTML elements that never have a closing tag, and therefore never need
     * the XHTML style "/" terminator prettier prints for them.
     *
     * @var array<int, string>
     */
    private const VOID = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
        'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    /**
     * The elements whose children are raw text, and whose content is therefore
     * never markup this pass may rewrite.
     *
     * @var array<int, string>
     */
    private const RAW_TEXT = ['script', 'style', 'textarea'];

    /**
     * Create a new strip void element slash instance.
     *
     * @param  bool  $voidElementSlash  Whether prettier's XHTML style terminator is kept, mirroring
     *                                  the "blade.void_element_slash" option in "pint.json".
     */
    public function __construct(private bool $voidElementSlash = true)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function postFormat(string $content): string
    {
        if ($this->voidElementSlash) {
            return $content;
        }

        $length = strlen($content);
        $result = '';
        $offset = 0;

        while ($offset < $length) {
            $character = $content[$offset];

            if ($character === '<') {
                $consumed = $this->consumeAngleBracket($content, $length, $offset);
            } elseif ($character === '{') {
                $consumed = $this->consumeUntil($content, $offset, '{{--', '--}}');
            } elseif ($character === '@') {
                $consumed = $this->consumePhpDirective($content, $offset);
            } else {
                $consumed = null;
            }

            if ($consumed === null) {
                $result .= $character;
                $offset++;

                continue;
            }

            [$text, $offset] = $consumed;
            $result .= $text;
        }

        return $result;
    }

    /**
     * Consume whatever the "<" at the given offset opens, or null when it opens nothing.
     *
     * @return array{string, int}|null
     */
    private function consumeAngleBracket(string $content, int $length, int $offset): ?array
    {
        if (($comment = $this->consumeUntil($content, $offset, '<!--', '-->')) !== null) {
            return $comment;
        }

        // A raw "<?php" island, or its "<?=" short echo.
        if (substr($content, $offset, 2) === '<?') {
            return $this->consumeUntil($content, $offset, '<?', '?>');
        }

        if (preg_match('/\G<([a-zA-Z][^\s\/>]*)/', $content, $matches, 0, $offset) !== 1) {
            return null;
        }

        $name = strtolower($matches[1]);

        $end = $this->tagEnd($content, $length, $offset);

        if ($end === null) {
            return null;
        }

        $tag = substr($content, $offset, $end - $offset + 1);

        if (in_array($name, self::RAW_TEXT, true)) {
            return $this->consumeRawText($content, $length, $tag, $name, $end + 1);
        }

        if (in_array($name, self::VOID, true)) {
            $tag = $this->stripSlash($tag, $matches[1]);
        }

        return [$tag, $end + 1];
    }

    /**
     * Consume a raw text element's content, up to (and including) its closing tag.
     *
     * @return array{string, int}
     */
    private function consumeRawText(string $content, int $length, string $openingTag, string $name, int $offset): array
    {
        if (str_ends_with($openingTag, '/>')) {
            return [$openingTag, $offset];
        }

        if (preg_match('/<\/'.preg_quote($name, '/').'\s*>/i', $content, $matches, PREG_OFFSET_CAPTURE, $offset) !== 1) {
            return [$openingTag.substr($content, $offset), $length];
        }

        $closingEnd = $matches[0][1] + strlen($matches[0][0]);

        return [$openingTag.substr($content, $offset, $closingEnd - $offset), $closingEnd];
    }

    /**
     * Consume the region opened by the given delimiter at the given offset, if it is there.
     *
     * @return array{string, int}|null
     */
    private function consumeUntil(string $content, int $offset, string $opening, string $closing): ?array
    {
        if (substr($content, $offset, strlen($opening)) !== $opening) {
            return null;
        }

        $end = strpos($content, $closing, $offset + strlen($opening));

        if ($end === false) {
            return [substr($content, $offset), strlen($content)];
        }

        $end += strlen($closing);

        return [substr($content, $offset, $end - $offset), $end];
    }

    /**
     * Consume an "@php ... @endphp" block, whose body is PHP rather than markup.
     *
     * @return array{string, int}|null
     */
    private function consumePhpDirective(string $content, int $offset): ?array
    {
        // "@php(...)" is an inline statement, not a block, and "@@php" is an escaped literal.
        if (preg_match('/\G@php\b(?!\s*\()/', $content, $matches, 0, $offset) !== 1) {
            return null;
        }

        if ($offset > 0 && $content[$offset - 1] === '@') {
            return null;
        }

        return $this->consumeUntil($content, $offset, '@php', '@endphp');
    }

    /**
     * Get the index of the ">" that closes the tag opened at the given offset, or null when there is none.
     */
    private function tagEnd(string $content, int $length, int $offset): ?int
    {
        $depth = 0;

        while ($offset < $length) {
            $character = $content[$offset];

            if ($character === '"' || $character === "'") {
                $close = strpos($content, $character, $offset + 1);

                if ($close === false) {
                    return null;
                }

                $offset = $close + 1;

                continue;
            }

            // A ">" inside an echo or a directive's argument list — "{{ $a > 1 }}",
            // or the "=>" of an "@class([...])" — is not the tag terminator.
            if ($character === '(' || $character === '[' || $character === '{') {
                $depth++;
            } elseif ($character === ')' || $character === ']' || $character === '}') {
                $depth = max(0, $depth - 1);
            } elseif ($character === '>' && $depth === 0) {
                return $offset;
            }

            $offset++;
        }

        return null;
    }

    /**
     * Drop the XHTML style terminator from a void element's tag.
     */
    private function stripSlash(string $tag, string $name): string
    {
        if (! str_ends_with($tag, '/>')) {
            return $tag;
        }

        $slash = strlen($tag) - 2;

        // Anything else in front of the "/" makes it part of an unquoted
        // attribute value — "<link href=/css/app.css/>" — not a terminator.
        if ($slash !== strlen($name) + 1 && ! in_array($tag[$slash - 1], [' ', "\t", "\n", "\r", '"', "'"], true)) {
            return $tag;
        }

        return rtrim(substr($tag, 0, $slash), " \t").'>';
    }
}
