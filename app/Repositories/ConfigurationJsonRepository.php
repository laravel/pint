<?php

namespace App\Repositories;

use App\Support\Project;
use Illuminate\Support\Arr;

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
     * Gets the configuration from the "pint.json" file.
     *
     * @return array<string, array<int, string>|string>
     */
    protected function get()
    {
        if (file_exists((string) $this->path)) {
            $configuration = json_decode(file_get_contents($this->path), true);

            if (! is_array($configuration)) {
                abort(1, sprintf('The configuration file [%s] is not valid JSON.', $this->path));
            }

            if ($configuration['extend'] ?? null) {
                $configuration = $this->resolve($configuration);
            }

            return $configuration;
        }

        return [];
    }

    /**
     * Resolves the "pint.json" file from extended configuration.
     *
     * @param  array<string, array<int, string>|string>  $configuration
     * @return array<string, array<int, string>|string>
     */
    protected function resolve($configuration)
    {
        if (! file_exists(Project::path().DIRECTORY_SEPARATOR.$configuration['extend'])) {
            abort(1, sprintf('The configuration file [%s] does not exist.', $configuration['extend']));
        }

        $parentConfiguration = (new ConfigurationJsonRepository($configuration['extend'], $this->preset))->get();

        $configuration = array_merge(Arr::except($parentConfiguration, 'rules'), $configuration);
        $configuration['rules'] = array_merge($parentConfiguration['rules'] ?? [], $configuration['rules'] ?? []);

        return $configuration;
    }
}
