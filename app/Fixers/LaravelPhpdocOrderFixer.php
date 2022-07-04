<?php

namespace App\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/*
 * Some code in this file is part of PHP CS Fixer.
 *
 * Copyright (c) 2012-2022 Fabien Potencier, Dariusz Rumiński
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class LaravelPhpdocOrderFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Laravel/laravel_phpdoc_order';
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Annotations must respect the following order: @param, @return, and @throws.',
            [],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return -2;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (! $token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $content = $token->getContent();
            $content = $this->moveParamAnnotations($content);
            $content = $this->moveThrowsAnnotations($content);
            $tokens[$index] = new Token([T_DOC_COMMENT, $content]);
        }
    }

    /**
     * Moves to the @params annotations on the given content.
     *
     * @param  string  $content
     * @return string
     */
    private function moveParamAnnotations($content)
    {
        $doc = new DocBlock($content);

        if (empty($params = $doc->getAnnotationsOfType('param'))) {
            return $content;
        }

        if (empty($others = $doc->getAnnotationsOfType(['throws', 'return']))) {
            return $content;
        }

        $end = end($params)->getEnd();

        $line = $doc->getLine($end);

        foreach ($others as $other) {
            if ($other->getStart() < $end) {
                $line->setContent($line->getContent().$other->getContent());
                $other->remove();
            }
        }

        return $doc->getContent();
    }

    /**
     * Moves to the @throws annotations on the given content.
     *
     * @param  string  $content
     * @return string
     */
    private function moveThrowsAnnotations($content)
    {
        $doc = new DocBlock($content);

        if (empty($throws = $doc->getAnnotationsOfType('throws'))) {
            return $content;
        }

        if (empty($others = $doc->getAnnotationsOfType(['param', 'return']))) {
            return $content;
        }

        $start = $throws[0]->getStart();
        $line = $doc->getLine($start);

        foreach (array_reverse($others) as $other) {
            if ($other->getEnd() > $start) {
                $line->setContent($other->getContent().$line->getContent());
                $other->remove();
            }
        }

        return $doc->getContent();
    }
}
