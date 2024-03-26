<?php

namespace App\Fixers\LaravelBlade\Processors;

use App\Contracts\PostProcessor;
use App\Contracts\PreProcessor;
use Illuminate\Support\Str;

class IgnoreCode implements PreProcessor, PostProcessor
{
    /**
     * The placeholders to ignore.
     *
     * @var array<string, string> $placeholders
     */
    protected $placeholders = [];

    /**
     * {@inheritDoc}
     */
    public function preProcess($content)
    {
        $content = $this->ignoreMinifiedCss($content);

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function postProcess($content)
    {
        foreach ($this->placeholders as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Ignore minified CSS.
     *
     * @param  string  $content
     * @return string
     */
    protected function ignoreMinifiedCss($content)
    {
        return preg_replace_callback('/<style(.*?)>(.*?)<\/style>/s', function ($matches) {
            if (! Str::of($matches[2])->replace([' ', "\n", "\r", "\t"], '')->startsWith(['/*!', '/* !'])) {
                return $matches[0];
            }

            $placeholder = sprintf('::%s::', uniqid('ignore-minified-css'));

            $ident = str_repeat(' ', Str::of(Str::of($matches[0])->explode("\n")->last())->before('</style>')->length());

            $this->placeholders[$placeholder] = trim($matches[2]);

            return '<style'.$matches[1].'>'."\n$ident    ".$placeholder."\n$ident</style>";
        }, $content);
    }
}
