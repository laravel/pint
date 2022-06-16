<?php

namespace App\Repositories;

class ConfigurationJsonRepository
{
    /**
     * @param  string  $path
     * return  void
     */
    public function __construct(protected $path)
    {
        //
    }

    /**
     * Gets the configuration.
     *
     * @return array<string, array<int, string>|string>
     */
    public function get()
    {
        $file = implode(DIRECTORY_SEPARATOR, [
            $this->path,
            'pint.json'
        ]);

        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        return [];
    }
}
