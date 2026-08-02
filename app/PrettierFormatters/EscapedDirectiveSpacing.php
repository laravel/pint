<?php

namespace App\PrettierFormatters;

use App\Contracts\PrettierPostFormatter;
use App\Contracts\PrettierPreFormatter;

class EscapedDirectiveSpacing implements PrettierPostFormatter, PrettierPreFormatter
{
    /**
     * The "word@@word" pairs the source already glued together.
     *
     * @var array<string, true>
     */
    private array $glued = [];

    /**
     * {@inheritDoc}
     *
     * Nothing is masked here; the pass only needs to know what the source looked
     * like before prettier saw it.
     */
    public function preFormat(string $content): string
    {
        preg_match_all('/(\w+)@@(?=(\w+))/', $content, $matches, PREG_SET_ORDER);

        $this->glued = [];

        foreach ($matches as $match) {
            $this->glued[$match[1].'@@'.$match[2]] = true;
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     *
     * Prettier's blade plugin eats the whitespace in front of an escaped directive
     * sitting in HTML text, joining "Text @@if" into "Text@@if". That is not a
     * cosmetic change: Blade only treats "@@" as an escape when it is not preceded
     * by a word character, so the escape stops working and the directive it was
     * meant to show literally is compiled instead. Put the separator back.
     *
     * A pair the source already glued together is left alone. Such a pair says
     * nothing about prettier: it is what the author wrote, and pint may not change
     * what a view renders - even when what it renders looks like a mistake. Only
     * that exact pair is spared, so an "a@@b" the author meant somewhere in the
     * file does not stop the "Text @@if" prettier broke from being repaired.
     */
    public function postFormat(string $content): string
    {
        return (string) preg_replace_callback(
            '/(\w+)@@(?=(\w+))/',
            fn (array $match): string => isset($this->glued[$match[1].'@@'.$match[2]])
                ? $match[0]
                : $match[1].' @@',
            $content,
        );
    }
}
