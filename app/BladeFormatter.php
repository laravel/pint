<?php

namespace App;

use App\Contracts\PostProcessor;
use App\Contracts\PreProcessor;

class BladeFormatter
{
    /**
     * Create a new blade formatter instance.
     *
     * @param  \App\Prettier  $prettier
     * @param  array<int, PreProcessor|PostProcessor>  $processors
     * @return void
     */
    public function __construct(
        protected $prettier,
        protected $processors,
    ) {
        //
    }

    /**
     * Format the given content.
     *
     * @param  string  $path
     * @param  string  $content
     * @return string
     */
    public function format($path, $content)
    {
        foreach ($this->processors as $processor) {
            if ($processor instanceof PreProcessor) {
                $content = $processor->preProcess($content);
            }
        }

        $content = $this->prettier->format($path, $content);

        foreach ($this->processors as $processor) {
            if ($processor instanceof PostProcessor) {
                $content = $processor->postProcess($content);
            }
        }

        return $content;
    }
}
