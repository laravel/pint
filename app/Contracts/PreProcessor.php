<?php

namespace App\Contracts;

interface PreProcessor
{
    /**
     * Process the content before the fixer runs.
     *
     * @param  string  $content
     * @return string
     */
    public function preProcess($content);
}
