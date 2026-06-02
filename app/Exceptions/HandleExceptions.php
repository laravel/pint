<?php

namespace App\Exceptions;

class HandleExceptions extends \Illuminate\Foundation\Bootstrap\HandleExceptions
{
    /**
     * {@inheritdoc}
     */
    protected function shouldIgnoreDeprecationErrors()
    {
        return true;
    }
}
