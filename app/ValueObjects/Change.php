<?php

namespace App\ValueObjects;

class Change
{
    /**
     * Creates a new Change instance.
     *
     * @param  string  $path
     * @param  string  $file
     * @param  array<string, array<int, string>>  $information
     */
    public function __construct(
        protected $path,
        protected $file,
        protected $information
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
     * Returns the number of detected issues.
     *
     * @return int
     */
    public function issues()
    {
        return count($this->information['appliedFixers']);
    }
}
