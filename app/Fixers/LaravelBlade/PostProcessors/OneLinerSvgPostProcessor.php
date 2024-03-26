<?php

namespace App\Fixers\LaravelBlade\PostProcessors;

use App\Contracts\PostProcessor;
use Illuminate\Support\Str;

class OneLinerSvgPostProcessor implements PostProcessor
{
    /**
     * {@inheritDoc}
     */
    public function postProcess($content)
    {
        return preg_replace_callback('/<svg(.*?)>(.*?)<\/svg>/s', function ($matches) use ($content) {
            $multiline = Str::of($matches[2])->startsWith("\n");
            $ident = $multiline ? (Str::of($matches[2])->before('<')->length() - 5) : 0;

            $tags = Str::of($matches[2])
                ->explode("\n")
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->map(fn ($line) => Str::of($line)->startsWith(['<', '>']) ? $line : (' '.$line))
                ->when(Str::startsWith($matches[2], "\n"), fn ($lines) => $lines->prepend("\n".  str_repeat(' ', $ident + 4)))
                ->when(Str::startsWith($matches[2], "\n"), function ($lines) use ($ident) {
                    return $lines->push("\n".str_repeat(' ', $ident));
                })
                ->implode('');

            return '<svg'.$matches[1].'>'.$tags.'</svg>';
        }, $content);
    }

    /**
     * Ensure no new line between tags.
     *
     * @param  string  $content
     * @return string
     */
    private function ensureNoNewLineBetweenTags($content)
    {
        $ident = Str::of($content)->replace("\n", '')->before('<');

        return Str::of($content)
            ->explode("\n")
            ->filter()
            ->values()
            ->map(fn ($line) => trim($line))
            ->map(fn ($line) => Str::of($line)->startsWith(['<', '>']) ? $line : (' '.$line))

            ->prepend($ident)
            ->when(Str::startsWith($content, "\n"), fn ($lines) => $lines->prepend("\n"))
            ->when(Str::endsWith($content, "\n"), fn ($lines) => $lines->push("\n"))
            ->implode('');
    }
}
