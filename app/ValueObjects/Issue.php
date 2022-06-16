<?php

namespace App\ValueObjects;

class Issue
{
    /**
     * Creates a new Change instance.
     *
     * @param  string  $path
     * @param  string  $file
     * @param  string  $symbol
     * @param  array<string, array<int, string>|\Throwable>  $payload
     */
    public function __construct(
        protected $path,
        protected $file,
        protected $symbol,
        protected $payload
    ) {
        // ..
    }

    /**
     * Returns the file where the change occur.
     *
     * @return string
     */
    public function file()
    {
        return str_replace($this->path.DIRECTORY_SEPARATOR, '', $this->file);
    }

    /**
     * Returns the issue's symbol.
     *
     * @return string
     */
    public function symbol()
    {
        return $this->symbol;
    }

    /**
     * Returns the issue's description.
     *
     * @param  bool  $testing
     * @return string
     */
    public function description($testing)
    {
        if (! empty($this->payload['source'])) {
            return $this->payload['source']->getMessage();
        }

        return collect($this->payload['appliedFixers'])->map(function ($appliedFixer) {
            return $appliedFixer;
        })->implode(', ');
    }

    /**
     * If the issue is an error.
     *
     * @return bool
     */
    public function isError()
    {
        return empty($this->payload['appliedFixers']);
    }
}
