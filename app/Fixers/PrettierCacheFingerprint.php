<?php

namespace App\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * A no-op fixer that never runs on any file. Its sole purpose is to
 * carry prettier dependency version fingerprints inside the rules array
 * so that they become part of PHP-CS-Fixer's cache Signature. When any
 * prettier-related package version changes, the fingerprints change,
 * the Signature no longer matches, and the entire cache is invalidated.
 *
 * This avoids the need to disable caching entirely when blade formatting
 * is enabled, while still guaranteeing correctness when external tool
 * versions change.
 *
 * @implements ConfigurableFixerInterface<array<string, mixed>, array<string, mixed>>
 */
class PrettierCacheFingerprint extends AbstractFixer implements ConfigurableFixerInterface
{
    /** @use ConfigurableFixerTrait<array<string, mixed>, array<string, mixed>> */
    use ConfigurableFixerTrait;

    public function getName(): string
    {
        return 'Pint/prettier_cache_fingerprint';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Carries prettier dependency version fingerprints for cache invalidation.',
            [],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return false;
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void {}

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('fingerprints', 'Map of fixer names to their prettier dependency version hashes.'))
                ->setAllowedTypes(['array'])
                ->setDefault([])
                ->getOption(),
        ]);
    }
}
