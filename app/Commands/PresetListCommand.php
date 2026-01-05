<?php

namespace App\Commands;

use App\Services\PresetManifest;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('preset:list', 'List all available presets')]
class PresetListCommand extends Command
{
    public function handle(PresetManifest $presetManifest): int
    {
        $presets = $presetManifest->names();

        if ($presets === []) {
            $this->components->warn('No presets found.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=green;options=bold>Preset</>',
            '<fg=yellow;options=bold>Path</>',
        );

        foreach ($presets as $preset) {
            $path = $presetManifest->path($preset);
            $presets[$preset] = $path;
            $this->components->twoColumnDetail($preset, $path);
        }

        return self::SUCCESS;
    }
}
