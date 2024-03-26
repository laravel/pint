<?php

namespace App\Contracts;

interface PreProcessor
{
    /**
     * Process the content before the fixer runs.
     */
    public function preProcess(string $content): string;
}
