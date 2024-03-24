<?php

namespace App\Fixers\LaravelBlade\Ignorables;

class Envoy
{
    /**
     * Whether the given blade file should be ignored.
     *
     * @param  string  $path
     * @return bool
     */
    public function __invoke($path)
    {
        return in_array(basename($path), [
            'envoy.blade.php',
            'Envoy.blade.php',
        ]);
    }
}
