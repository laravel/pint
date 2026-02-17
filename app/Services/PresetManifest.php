<?php

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class PresetManifest
{
    /** @var ?array<string, string> */
    protected ?array $manifest = null;

    protected string $vendorPath;

    public function __construct(
        protected Filesystem $files,
        protected string $basePath,
        protected string $manifestPath,
    ) {
        $this->vendorPath = $this->basePath.'/vendor';
    }

    /**
     * Get all available presets from packages.
     *
     * @return array<string, string> ['preset-name' => '/absolute/path/to/preset.php']
     */
    public function presets(): array
    {
        return $this->manifest ??= $this->getManifest();
    }

    /**
     * Check if a preset exists.
     */
    public function has(string $preset): bool
    {
        return array_key_exists($preset, $this->presets());
    }

    /**
     * Get the path for a specific preset.
     */
    public function path(string $preset): ?string
    {
        return $this->presets()[$preset] ?? null;
    }

    /**
     * Get all preset names.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->presets());
    }

    /**
     * Get the current preset manifest.
     *
     * @return array<string, string>
     */
    protected function getManifest(): array
    {
        $path = $this->vendorPath.'/composer/installed.json';

        if (
            ! $this->files->exists($this->manifestPath) ||
            $this->files->lastModified($path) > $this->files->lastModified($this->manifestPath)
        ) {
            return $this->build();
        }

        return $this->files->getRequire($this->manifestPath);
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return array<string, string>
     */
    protected function build(): array
    {
        $packages = [];
        $installedPath = $this->vendorPath.'/composer/installed.json';
        $composerPath = $this->basePath.'/composer.json';

        if ($this->files->exists($installedPath)) {
            $installed = json_decode($this->files->get($installedPath), true);
            $packages = $installed['packages'] ?? $installed;
        }

        $presets = (new Collection($packages))
            ->keyBy(fn ($package) => $this->vendorPath.'/'.$package['name'])
            ->when($this->files->exists($composerPath), function ($presets) use ($composerPath) {
                $composer = json_decode($this->files->get($composerPath), true);

                return $presets->put($this->basePath, $composer);
            })
            ->map(fn ($package) => $package['extra']['laravel-pint']['presets'] ?? [])
            ->flatMap(function (array $presets, string $basePath): array {
                foreach ($presets as $name => $relativePath) {
                    $absolutePath = $basePath.'/'.$relativePath;

                    if ($this->files->exists($absolutePath)) {
                        $presets[$name] = $absolutePath;
                    } else {
                        unset($presets[$name]);
                    }
                }

                return $presets;
            })
            ->all();

        $this->write($presets);

        return $presets;
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array<string, string>  $manifest
     */
    protected function write(array $manifest): void
    {
        $this->files->ensureDirectoryExists(dirname($this->manifestPath), 0755, true);
        $this->files->replace($this->manifestPath, '<?php return '.var_export($manifest, true).';');
    }
}
