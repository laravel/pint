<?php

namespace App\Contracts;

interface PrettierPostFormatter
{
    /**
     * Post-format the given (already prettier-formatted) content.
     */
    public function postFormat(string $content): string;
}
