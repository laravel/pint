<?php

namespace App\Fixers\LaravelBlade;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Decorates a fixer so that it never touches Blade templates.
 *
 * Raw "<?php" chunks inside Blade files are tokenized as standalone
 * root-namespace PHP, so fixers that inject, remove or rewrite import
 * statements corrupt the template when applied to them.
 *
 * @internal
 */
class SkipBladeFilesFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    public function __construct(protected readonly FixerInterface $fixer)
    {
        parent::__construct();
    }

    public function getName(): string
    {
        return $this->fixer->getName();
    }

    public function supports(SplFileInfo $file): bool
    {
        return ! str_ends_with($file->getFilename(), '.blade.php')
            && $this->fixer->supports($file);
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->fixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->fixer->isRisky();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->fixer->getDefinition();
    }

    public function getPriority(): int
    {
        return $this->fixer->getPriority();
    }

    public function configure(array $configuration): void
    {
        if (! $this->fixer instanceof ConfigurableFixerInterface) {
            return;
        }

        $this->fixer->configure($configuration);
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        /** @var ConfigurableFixerInterface&FixerInterface */
        $fixer = $this->fixer;

        return $fixer->getConfigurationDefinition();
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $this->fixer->fix($file, $tokens);
    }
}
