<?php

namespace App\Fixers;

use App\Fixers\Utils\PhpdocTagComparator;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

class LaravelPhpdocSeparationFixer extends AbstractFixer
{
    /**
     * Returns the name of the fixer.
     * The name must match the pattern /^[A-Z][a-zA-Z0-9]*\/[a-z][a-z0-9_]*$/
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Laravel/laravel_phpdoc_separation';
    }

    /**
     * Returns the definition of the fixer.
     *
     * @return \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Annotations in PHPDoc should be grouped together so that annotations of the same type immediately follow each other, and annotations of a different type are separated by a single blank line. @param and @return are of the same type',
            [
                new CodeSample(
                    '<?php
/**
 * Description.
 * @param string $foo
 *
 *
 * @param bool   $bar Bar
 * @throws Exception|RuntimeException
 * @return bool
 */
function fnc($foo, $bar) {}
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
     *
     * Must run after
     * - AlignMultilineCommentFixer
     * - CommentToPhpdocFixer
     * - GeneralPhpdocAnnotationRemoveFixer
     * - PhpdocIndentFixer, PhpdocNoAccessFixer
     * - PhpdocNoEmptyReturnFixer
     * - PhpdocNoPackageFixer
     * - PhpdocOrderFixer
     * - PhpdocScalarFixer
     * - PhpdocToCommentFixer
     * - PhpdocTypesFixer.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return -3;
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

            $doc = new DocBlock($token->getContent());
            $this->fixDescription($doc);
            $this->fixAnnotations($doc);

            $tokens[$index] = new Token([T_DOC_COMMENT, $doc->getContent()]);
        }
    }

    /**
     * Make sure the description is separated from the annotations.
     *
     * @param  \PhpCsFixer\DocBlock\DocBlock  $doc
     * @return void
     */
    private function fixDescription($doc)
    {
        foreach ($doc->getLines() as $index => $line) {
            if ($line->containsATag()) {
                break;
            }

            if ($line->containsUsefulContent()) {
                $next = $doc->getLine($index + 1);

                if (null !== $next && $next->containsATag()) {
                    $line->addBlank();

                    break;
                }
            }
        }
    }

    /**
     * Make sure the annotations are correctly separated.
     *
     * @param  \PhpCsFixer\DocBlock\DocBlock  $doc
     * @return void
     */
    private function fixAnnotations($doc)
    {
        foreach ($doc->getAnnotations() as $index => $annotation) {
            $next = $doc->getAnnotation($index + 1);

            if (null === $next) {
                break;
            }

            if (true === $next->getTag()->valid()) {
                if (PhpdocTagComparator::shouldBeTogether($annotation->getTag(), $next->getTag())) {
                    $this->ensureAreTogether($doc, $annotation, $next);
                } else {
                    $this->ensureAreSeparate($doc, $annotation, $next);
                }
            }
        }
    }

    /**
     * Force the given annotations to immediately follow each other.
     * This is done by removing the blank lines between them.
     *
     * @param  \PhpCsFixer\DocBlock\DocBlock  $doc
     * @param  \PhpCsFixer\DocBlock\Annotation  $annotation
     * @param  \PhpCsFixer\DocBlock\Annotation  $next
     * @return void
     */
    private function ensureAreTogether($doc, $annotation, $next)
    {
        $pos = $annotation->getEnd();
        $final = $next->getStart();

        for ($pos = $pos + 1; $pos < $final; $pos++) {
            $doc->getLine($pos)->remove();
        }
    }

    /**
     * Force the given annotations to have one empty line between each other.
     * This is done by adding a blank line between them or reducing the number
     * of blank lines between them to one.
     *
     * @param  \PhpCsFixer\DocBlock\DocBlock  $doc
     * @param  \PhpCsFixer\DocBlock\Annotation  $annotation
     * @param  \PhpCsFixer\DocBlock\Annotation  $next
     * @return void
     */
    private function ensureAreSeparate($doc, $annotation, $next)
    {
        $pos = $annotation->getEnd();
        $final = $next->getStart() - 1;

        // check if we need to add a line, or need to remove one or more lines
        if ($pos === $final) {
            $doc->getLine($pos)->addBlank();

            return;
        }

        for ($pos = $pos + 1; $pos < $final; $pos++) {
            $doc->getLine($pos)->remove();
        }
    }
}
