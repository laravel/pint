<?php

namespace App\Support;

class Project
{
    /**
     * The project being analysed path.
     *
     * @return string
     */
    public static function path()
    {
        return getcwd();
    }
}
