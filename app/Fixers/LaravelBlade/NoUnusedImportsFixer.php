<?php

namespace App\Fixers\LaravelBlade;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer as BaseNoUnusedImportsFixer;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class NoUnusedImportsFixer extends AbstractFixer
{
    /**
     * Create a new blade-aware "no_unused_imports" fixer instance.
     */
    public function __construct(protected BaseNoUnusedImportsFixer $fixer)
    {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function supports(SplFileInfo $file): bool
    {
        return ! str_ends_with($file->getFilename(), '.blade.php') && $this->fixer->supports($file);
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
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $this->fixer->fix($file, $tokens);
    }
}
