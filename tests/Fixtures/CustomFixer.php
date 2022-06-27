<?php

namespace Tests\Fixtures;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class CustomFixer implements FixerInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'ACMECorp/custom_rule';
    }

    /**
     * @inheritdoc
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isRisky(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (! $tokens[$index]->isGivenKind([\T_COMMENT])) {
                continue;
            }

            $content = preg_replace('/^\/\/\s/', "//\n// ", $tokens[$index]->getContent(), 1);

            $tokens[$index] = new Token([T_COMMENT, $content]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Lines starting with single-line comments as first characters should be converted to double-line comments.', []);
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function supports(SplFileInfo $file): bool
    {
        return true;
    }
}
