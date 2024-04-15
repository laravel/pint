<?php

namespace App\Fixers\LaravelBlade\Processors;

use App\Contracts\PostProcessor;
use App\Contracts\PreProcessor;
use DOMElement;
use DOMException;
use ErrorException;
use Illuminate\Support\Str;

class IgnoreCode implements PostProcessor, PreProcessor
{
    /**
     * The placeholders to ignore.
     *
     * @var array<string, string>
     */
    protected $placeholders = [];

    /**
     * {@inheritDoc}
     */
    public function preProcess($content)
    {
        $content = $this->ignoreMinifiedCss($content);
        // $content = $this->ignoreText($content);

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function postProcess($content)
    {
        foreach (array_reverse($this->placeholders) as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        $this->placeholders = [];

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

    protected function ignoreText($content)
    {
        $dom = new \DOMDocument();

        $useInternalErrors = libxml_use_internal_errors(true);

        try {
            $dom->loadHTML($content);
        } catch (ErrorException $e) {
            return $content;
        } finally {
            libxml_use_internal_errors($useInternalErrors);
        }

        $elements = $dom->getElementsByTagName('*');

        foreach ($elements as $element) {
            $content = $this->foreachElement($element, function ($element, $content) use ($dom) {

                $textContent = $element->firstChild?->textContent;

                if (! is_null($textContent) && $this->isText($textContent) && $element->childNodes->length > 1) {
                    try {
                        $html = $dom->saveHTML($element);
                    } catch (DOMException) {
                        return $content;
                    }

                    foreach (Str::of($html)->after('>')->beforeLast('<')->explode("\n") as $key => $line) {
                        $placeholder = sprintf('::%s::', uniqid('ignore-text'));
                        $this->placeholders[$placeholder] = $html;

                        $content = str_replace($html, $placeholder, $content);
                    }
                }

                return $content;
            }, $content);
        }

        return $content;
    }

    /**
     * Runs the given callback for each element.
     *
     * @param  DOMElement  $element
     * @param  callable  $callback
     * @param  string  $content
     * @return string
     */
    protected function foreachElement($element, $callback, $content)
    {
        $content = $callback($element, $content);

        foreach ($element->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $content = $this->foreachElement($child, $callback, $content);
        }

        return $content;
    }

    /**
     * Check if the given text is not empty.
     *
     * @param  string  $text
     * @return bool
     */
    protected function isText($text)
    {
        $text = str_replace([' ', "\n", "\r", "\t"], '', $text);

        return $text !== '' && ! Str::startsWith($text, '@');
    }
}
