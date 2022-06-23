<?php

namespace App\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

class LaravelPhpdocOrderFixer extends AbstractFixer
{
    /**
     * Returns the name of the fixer.
     * The name must match the pattern /^[A-Z][a-zA-Z0-9]*\/[a-z][a-z0-9_]*$/
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Laravel/laravel_phpdoc_order';
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
     * @param  \PhpCsFixer\Tokenizer\Tokens  $tokens
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * Returns the definition of the fixer.
     *
     * @return \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Annotations in PHPDoc should be ordered so that `@param` annotations come first, then `@return` annotations, then `@throws` annotations.',
            [
                new CodeSample(
                    '<?php
/**
 * Hello there!
 *
 * @throws Exception|RuntimeException foo
 * @custom Test!
 * @return int  Return the number of changes.
 * @param string $foo
 * @param bool   $bar Bar
 */
'
                ),
            ]
        );
    }

    /**
     * Returns the priority of the fixer.
     *
     * The default priority is 0 and higher priorities are executed first.
     *
     * Must run before
     * - PhpdocAlignFixer
     * - PhpdocSeparationFixer
     * - PhpdocTrimFixer.
     *
     * Must run after
     * - AlignMultilineCommentFixer
     * - CommentToPhpdocFixer
     * - PhpdocAddMissingParamAnnotationFixer
     * - PhpdocIndentFixer
     * - PhpdocNoEmptyReturnFixer
     * - PhpdocScalarFixer
     * - PhpdocToCommentFixer
     * - PhpdocTypesFixer.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return -2;
    }

    /**
     * Fixes a file.
     *
     * @param  \SplFileInfo  $file
     * @param  \PhpCsFixer\Tokenizer\Tokens  $tokens
     * @return void
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (! $token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $content = $token->getContent();
            // move param to start, throws to end, leave return in the middle
            $content = $this->moveParamAnnotations($content);
            // we're parsing the content again to make sure the internal
            // state of the docblock is correct after the modifications
            $content = $this->moveThrowsAnnotations($content);
            // persist the content at the end
            $tokens[$index] = new Token([T_DOC_COMMENT, $content]);
        }
    }

    /**
     * Move all `param` annotations to be before `throws` and `return` annotations.
     *
     * @param  string  $content
     * @return string
     */
    private function moveParamAnnotations($content)
    {
        $doc = new DocBlock($content);
        $params = $doc->getAnnotationsOfType('param');

        // nothing to do if there are no param annotations
        if (0 === \count($params)) {
            return $content;
        }

        $others = $doc->getAnnotationsOfType(['throws', 'return']);

        if (0 === \count($others)) {
            return $content;
        }

        // get the index of the final line of the final param annotation
        $end = end($params)->getEnd();

        $line = $doc->getLine($end);

        // move stuff about if required
        foreach ($others as $other) {
            if ($other->getStart() < $end) {
                // we're doing this to maintain the original line indices
                $line->setContent($line->getContent().$other->getContent());
                $other->remove();
            }
        }

        return $doc->getContent();
    }

    /**
     * Move all `return` annotations to be after `param` and `throws` annotations.
     *
     * @param  string  $content
     * @return string
     */
    private function moveThrowsAnnotations($content)
    {
        $doc = new DocBlock($content);
        $throws = $doc->getAnnotationsOfType('throws');

        // nothing to do if there are no return annotations
        if (0 === \count($throws)) {
            return $content;
        }

        $others = $doc->getAnnotationsOfType(['param', 'return']);

        // nothing to do if there are no other annotations
        if (0 === \count($others)) {
            return $content;
        }

        // get the index of the first line of the first return annotation
        $start = $throws[0]->getStart();
        $line = $doc->getLine($start);

        // move stuff about if required
        foreach (array_reverse($others) as $other) {
            if ($other->getEnd() > $start) {
                // we're doing this to maintain the original line indices
                $line->setContent($other->getContent().$line->getContent());
                $other->remove();
            }
        }

        return $doc->getContent();
    }
}
