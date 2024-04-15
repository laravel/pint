<?php

namespace App\Contracts;

interface PostProcessor
{
    /**
     * Process the content after the fixer has run.
     *
     * @param  string  $content
     * @return string
     */
    public function postProcess($content);
}
