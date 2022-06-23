<?php

namespace App\Fixers;

use PhpCsFixer\DocBlock\TypeExpression;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class LaravelPhpdocAlignmentFixer implements FixerInterface
{
    /**
     * Returns the name of the fixer.
     * The name must match the pattern /^[A-Z][a-zA-Z0-9]*\/[a-z][a-z0-9_]*$/
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Laravel/laravel_phpdoc_alignment';
    }

    /**
     * Check if the fixer is a candidate for given Tokens collection.
     *
     * Fixer is a candidate when the collection contains tokens that may be fixed
     * during fixer work. This could be considered as some kind of bloom filter.
     * When this method returns true then to the Tokens collection may or may not
     * need a fixing, but when this method returns false then the Tokens collection
     * need no fixing for sure.
     *
     * @param  \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([\T_DOC_COMMENT]);
    }


    /**
     * Check if fixer is risky or not.
     *
     * Risky fixer could change code behavior!
     *
     * @return bool
     */
    public function isRisky(): bool
    {
        return false;
    }

    /**
     * Fixes a file.
     *
     * @param  \SplFileInfo $file
     * @param  \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return void
     */
    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (! $tokens[$index]->isGivenKind([\T_DOC_COMMENT])) {
                continue;
            }

            $newContent = preg_replace_callback(
                '/(?P<tag>@param)\s+(?P<hint>(?:'.TypeExpression::REGEX_TYPES.')?)\s+(?P<var>(?:&|\.{3})?\$\S+)/ux',
                function ($matches) {
                    return $matches['tag'].'  '.$matches['hint'].'  '.$matches['var'];
                },
                $tokens[$index]->getContent()
            );

            if ($newContent === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([\T_DOC_COMMENT, $newContent]);
        }
    }

    /**
     * Returns the definition of the fixer.
     *
     * @return \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('After @param must be two spaces and after the Type Definition must also be two spaces.', [
            new CodeSample('<?php
/**
 * @param string $foo
 * @param  string  $bar
 * @return string
 */
function a($foo, $bar) {}
'),
        ]);
    }

    /**
     * Returns the priority of the fixer.
     *
     * The default priority is 0 and higher priorities are executed first.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return -42;
    }

    /**
     * Returns true if the file is supported by this fixer.
     *
     * @return bool
     */
    public function supports(SplFileInfo $file): bool
    {
        return true;
    }
}
