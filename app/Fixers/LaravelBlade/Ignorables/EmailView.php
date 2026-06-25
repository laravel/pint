<?php

namespace App\Fixers\LaravelBlade\Ignorables;

class EmailView
{
    /**
     * Whether the given file content should be ignored.
     */
    public function __invoke(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return str_contains($path, 'resources/views/emails/')
            || str_contains($path, 'resources/views/mail/');
    }
}
