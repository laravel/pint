<?php

namespace App\Repositories;

class ConfigurationJsonRepository
{
    /**
     * Lists the finder options.
     *
     * @var array<int, string>
     */
    protected $finderOptions = [
        'exclude',
    ];

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
    public function finder()
    {
        return collect($this->get())
            ->filter(fn ($value, $key) => in_array($key, $this->finderOptions))
            ->toArray();
    }

    /**
     * Gets the configuration.
     *
     * @return array<int, string>
     */
    public function rules()
    {
        return $this->get()['rules'] ?? [];
    }

    /**
     * Gets the configuration.
     *
     * @return array<string, array<int, string>|string>
     */
    protected function get()
    {
        $file = implode(DIRECTORY_SEPARATOR, [
            $this->path,
            'pint.json',
        ]);

        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        return [];
    }
}
