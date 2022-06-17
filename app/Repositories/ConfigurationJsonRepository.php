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
     * @param  string|null  $preset
     * @return void
     */
    public function __construct(protected $path, protected $preset)
    {
        //
    }

    /**
     * Gets the finder options.
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
     * Gets the rules options.
     *
     * @return array<int, string>
     */
    public function rules()
    {
        return $this->get()['rules'] ?? [];
    }

    /**
     * Gets the preset option.
     *
     * @return string
     */
    public function preset()
    {
        return $this->preset ?: ($this->get()['preset'] ?? 'psr12');
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
