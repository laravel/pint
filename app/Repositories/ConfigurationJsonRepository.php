<?php

namespace App\Repositories;

use App\Project;
use PhpToken;

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
     * Lists the custom fixers.
     *
     * @var array<int, string>
     */
    protected $customFixerList = [
        'App\Fixers\LaravelPhpdocAlignmentFixer',
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
     * Get the custom fixers.
     *
     * @return array<int, CustomFixerInterface>
     */
    public function customFixers()
    {
        $fixers = $this->getRegisteredClasses($this->get()['custom-fixers'] ?? []);

        $this->customFixerList = [...$this->customFixerList, ...$fixers];

        return collect($this->customFixerList)
            ->map(fn ($fixer) => new $fixer())
            ->toArray();
    }

    /**
     * Get fixer classes name from the "pint.json" file.
     *
     * @return array<int, string>
     */
    protected function getRegisteredClasses(array $classes)
    {
        return collect($classes)
            ->map(function ($class) {
                $file = Project::path().'/'.$class;

                spl_autoload_register(fn () => require_once($file));

                $tokens = PhpToken::tokenize(file_get_contents($file));

                $namespace = null;

                foreach ($tokens as $key => $token) {
                    if ($token->id == T_NAMESPACE) {
                        $namespace = $tokens[$key + 2]->text;
                        break;
                    }
                }

                return $namespace.'\\'.basename($class, '.php');
            })->toArray();
    }

    /**
     * Get the configuration from the "pint.json" file.
     *
     * @return array<string, array<int, string>|string>
     */
    protected function get()
    {
        if (file_exists((string) $this->path)) {
            return tap(json_decode(file_get_contents($this->path), true), function ($configuration) {
                if (! is_array($configuration)) {
                    abort(1, sprintf('The configuration file [%s] is not valid JSON.', $this->path));
                }
            });
        }

        return [];
    }
}
