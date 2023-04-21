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
        'notPath',
        'notName',
    ];

    /**
     * Create a new Configuration Json Repository instance.
     *
     * @param  string|array<int, string>|null  $path
     * @param  string|null  $preset
     * @return void
     */
    public function __construct(protected $path, protected $preset)
    {
        //
    }

    /**
     * Get the finder options.
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
     * Get the rules options.
     *
     * @return array<int, string>
     */
    public function rules()
    {
        return $this->get()['rules'] ?? [];
    }

    /**
     * Get the cache file location.
     *
     * @return string|null
     */
    public function cacheFile()
    {
        return $this->get()['cache-file'] ?? null;
    }

    /**
     * Get the preset option.
     *
     * @return string
     */
    public function preset()
    {
        return $this->preset ?: ($this->get()['preset'] ?? 'laravel');
    }

    /**
     * Get the configuration from the "pint.json" file.
     *
     * @return array<string, array<int, string>|string>
     */
    protected function get()
    {
        $paths = is_array($this->path) ? $this->path : [$this->path];

        return array_reduce($paths, function ($carry, $path) {
            if (! file_exists((string) $path)) {
                return $carry;
            }

            return array_merge(
                $carry,
                tap(
                    json_decode(file_get_contents($path), true),
                    function ($configuration) use ($path) {
                        if (! is_array($configuration)) {
                            abort(1, sprintf('The configuration file [%s] is not valid JSON.', $path));
                        }
                    }
                )
            );
        }, []);
    }
}
