<?php

namespace App\Enums;

use Illuminate\Support\Facades\File;

enum NodePackageManager: string
{
    case Npm = 'npm';
    case Yarn = 'yarn';
    case Pnpm = 'pnpm';
    case Bun = 'bun';

    /**
     * Detect the package manager from the project's lock file, defaulting to npm.
     */
    public static function detect(string $projectRoot): self
    {
        return match (true) {
            File::exists($projectRoot.'/bun.lock'),
            File::exists($projectRoot.'/bun.lockb') => self::Bun,
            File::exists($projectRoot.'/pnpm-lock.yaml') => self::Pnpm,
            File::exists($projectRoot.'/yarn.lock') => self::Yarn,
            default => self::Npm,
        };
    }

    /**
     * The command used to install the given packages as development dependencies.
     *
     * @param  array<int, string>  $packages
     * @return array<int, string>
     */
    public function installCommand(array $packages): array
    {
        return match ($this) {
            self::Npm => ['npm', 'install', '-D', ...$packages],
            self::Yarn => ['yarn', 'add', '-D', ...$packages],
            self::Pnpm => ['pnpm', 'add', '-D', ...$packages],
            self::Bun => ['bun', 'add', '-d', ...$packages],
        };
    }

    /**
     * The package manager binary name.
     */
    public function binary(): string
    {
        return $this->value;
    }
}
