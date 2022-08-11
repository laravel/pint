<?php

namespace App\Repositories;

use Illuminate\Support\Str;

class ConfigurationJsonRepository
{
    /**
     * Lists the finder options.
     *
     * @var array<int, string>
     */
    protected $finderOptions = [
        'exclude',
        'notPath',
        'notName',
    ];

    /**
     * Creates a new Configuration Json Repository instance.
     *
     * @param  string|null  $path
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
        return $this->preset ?: ($this->get()['preset'] ?? 'laravel');
    }

    /**
     * Gets the configuration from the "pint.json" or "pint.php" file.
     *
     * @return array<string, array<int, string>|string>
     */
    protected function get()
    {
        if (! file_exists((string) $this->path)) {
            return [];
        }

        if (Str::endsWith($this->path, '.json')) {
            return tap(json_decode(file_get_contents($this->path), true), function ($configuration) {
                if (! is_array($configuration)) {
                    abort(1, sprintf('The configuration file [%s] is not valid JSON.', $this->path));
                }
            });
        }
        if (Str::endsWith($this->path, '.php')) {
            return require $this->path;
        }

        return [];
    }
}
