<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Http;

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
     * @return void
     */
    public function __construct(protected ?string $path, protected ?string $preset, protected ConfigurationLoaderResolver $configurationLoader)
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
        $loader = $this->configurationLoader->resolveFor($this->path);
        $config = $loader->load($this->path);

        if ($config) {
            return tap(json_decode($config, true), function ($configuration) {
                if (! is_array($configuration)) {
                    abort(1, sprintf('The configuration file [%s] is not valid JSON.', $this->path));
                }
            });
        }

        return [];
    }

    protected function fileExists(string $path): bool
    {
        return file_exists($path) || (filter_var($this->path, FILTER_VALIDATE_URL) && Http::get($path)->ok());
    }
}
