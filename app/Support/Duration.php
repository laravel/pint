<?php

namespace App\Support;

class Duration
{
    /**
     * Formats the given duration, in seconds, in a human-readable format.
     *
     * @param  float  $seconds
     * @return string
     */
    public static function format($seconds)
    {
        if ($seconds >= 60) {
            return sprintf('%dm %02ds', intdiv((int) $seconds, 60), (int) $seconds % 60);
        }

        return sprintf('%.2fs', $seconds);
    }
}
