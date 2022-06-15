<?php

namespace App\ValueObjects;

class Change
{
    /**
     * Creates a new change instance.
     */
    public function __construct(
        protected string $path,
        protected string $file,
        protected array $information
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
