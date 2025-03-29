<?php

namespace Tests\Fixtures\custom;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

class CustomFixer implements FixerInterface {

    public function getName(): string {
        return 'My/CustomFixer';
    }

    public function getDefinition(): FixerDefinitionInterface {
        return new FixerDefinition('A custom fixer', []);
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void {
        //
    }

    public function isCandidate(Tokens $tokens): bool {
        return true;
    }

    public function supports(\SplFileInfo $file): bool {
        return true;
    }

    public function isRisky(): bool {
        return false;
    }

    public function getPriority(): int {
        return 0;
    }

}
