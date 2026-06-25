<?php

namespace App\Contracts;

interface PrettierPreFormatter
{
    /**
     * Pre-format the given content before it is handed to prettier.
     */
    public function preFormat(string $content): string;
}
