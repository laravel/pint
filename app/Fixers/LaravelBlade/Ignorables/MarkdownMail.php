<?php

namespace App\Fixers\LaravelBlade\Ignorables;

class MarkdownMail
{
    /**
     * Whether the given blade file should be ignored.
     *
     * @param  string  $content
     * @return bool
     */
    public function __invoke($content)
    {
        return str_contains($content, '<x-mail::') || str_contains($content, '@component(\'mail::');
    }
}
