<?php

namespace Another\Directory;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

class IsNotAFixer implements FixerInterface
{
    private $whitespacesConfig;

    public function getName(): string
    {
        return 'not_a_fixer';
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Is not a fixer', []);
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        // do nothing
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }
}