<?php

namespace App\Fixers\LaravelBlade;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;

/**
 * Decorates a fixer so that it never touches Blade templates.
 *
 * Raw "<?php" chunks inside Blade files are tokenized as standalone
 * root-namespace PHP, so fixers that inject, remove or rewrite import
 * statements corrupt the template when applied to them.
 *
 * @implements ConfigurableFixerInterface<array<string, mixed>, array<string, mixed>>
 *
 * @internal
 */
class SkipBladeFilesFixer implements ConfigurableFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * Create a new blade-aware fixer instance.
     */
    public function __construct(protected readonly FixerInterface $fixer)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->fixer->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function supports(SplFileInfo $file): bool
    {
        return ! str_ends_with($file->getFilename(), '.blade.php')
            && $this->fixer->supports($file);
    }

    /**
     * {@inheritDoc}
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $this->fixer->isCandidate($tokens);
    }

    /**
     * {@inheritDoc}
     */
    public function isRisky(): bool
    {
        return $this->fixer->isRisky();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->fixer->getDefinition();
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority(): int
    {
        return $this->fixer->getPriority();
    }

    /**
     * {@inheritDoc}
     */
    public function configure(array $configuration): void
    {
        if (! $this->fixer instanceof ConfigurableFixerInterface) {
            return;
        }

        $this->fixer->configure($configuration);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        /** @var ConfigurableFixerInterface<array<string, mixed>, array<string, mixed>>&FixerInterface */
        $fixer = $this->fixer;

        return $fixer->getConfigurationDefinition();
    }

    /**
     * {@inheritDoc}
     */
    public function setWhitespacesConfig(WhitespacesFixerConfig $config): void
    {
        if (! $this->fixer instanceof WhitespacesAwareFixerInterface) {
            return;
        }

        $this->fixer->setWhitespacesConfig($config);
    }

    /**
     * {@inheritDoc}
     */
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        if (! $this->supports($file)) {
            return;
        }

        $this->fixer->fix($file, $tokens);
    }
}
