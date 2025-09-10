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
     * @param  string|null  $path
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
        if (! is_null($this->path) && $this->fileExists((string) $this->path)) {
            $baseConfig = json_decode(file_get_contents($this->path), true);

            if (isset($baseConfig['extend'])) {
                $baseConfig = $this->resolveExtend($baseConfig);
            }

            if (isset($baseConfig['rules'])) {
                $baseConfig['rules'] = $this->normalizeRuleValues($baseConfig['rules']);
            }

            return tap($baseConfig, function ($configuration) {
                if (! is_array($configuration)) {
                    abort(1, sprintf('The configuration file [%s] is not valid JSON.', $this->path));
                }
            });
        }

        return [];
    }

    /**
     * Normalize shorthand rule values into explicit configuration arrays as expected by PHP-CS-Fixer.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function normalizeRuleValues(array $rules): array
    {
        if (array_key_exists('cast_spaces', $rules)) {
            $rules['cast_spaces'] = match ($rules['cast_spaces']) {
                false => ['space' => 'none'],
                true => ['space' => 'single'],
                default => $rules['cast_spaces'],
            };
        }

        return $rules;
    }

    /**
     * Determine if a local or remote file exists.
     *
     * @return bool
     */
    protected function fileExists(string $path)
    {
        return match (true) {
            str_starts_with($path, 'http://') || str_starts_with($path, 'https://') => str_contains(get_headers($path)[0], '200 OK'),
            default => file_exists($path)
        };
    }

    /**
     * Resolve the file to extend.
     *
     * @param  array<string, array<int, string>|string>  $configuration
     * @return array<string, array<int, string>|string>
     */
    private function resolveExtend(array $configuration)
    {
        $path = realpath(dirname($this->path).DIRECTORY_SEPARATOR.$configuration['extend']);

        $extended = json_decode(file_get_contents($path), true);

        if (isset($extended['extend'])) {
            throw new \LogicException('Pint configuration cannot extend from more than 1 file.');
        }

        return array_replace_recursive($extended, $configuration);
    }
}
