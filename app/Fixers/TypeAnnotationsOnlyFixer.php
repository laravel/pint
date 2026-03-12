<?php

namespace App\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class TypeAnnotationsOnlyFixer extends AbstractFixer
{
    public function getName(): string
    {
        return 'Pint/phpdoc_type_annotations_only';
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Remove all comments except those containing `@` annotations.',
            [new CodeSample("<?php\n// This is a comment\n\$x = 1;\n")],
        );
    }

    /**
     * Must run before NoExtraBlankLinesFixer, NoTrailingWhitespaceFixer, NoWhitespaceInBlankLineFixer.
     * Must run after PhpdocToCommentFixer.
     */
    public function getPriority(): int
    {
        return 2;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_COMMENT)
            || $tokens->isTokenKindFound(\T_DOC_COMMENT);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = \count($tokens) - 1; $index >= 0; $index--) {
            if ($tokens[$index]->isGivenKind(\T_COMMENT)) {
                $this->processComment($tokens, $index);
            } elseif ($tokens[$index]->isGivenKind(\T_DOC_COMMENT)) {
                $this->processDocComment($tokens, $index);
            }
        }
    }

    private function processComment(Tokens $tokens, int $index): void
    {
        $content = $tokens[$index]->getContent();

        if (str_contains($content, '@')) {
            return;
        }

        if ($this->isBodyPlaceholder($tokens, $index)) {
            $placeholder = str_starts_with(ltrim($content), '#') ? '#' : '//';

            if (trim($content) !== $placeholder) {
                $tokens[$index] = new Token([\T_COMMENT, $placeholder]);
            }

            return;
        }

        $this->clearAndCleanWhitespace($tokens, $index);
    }

    private function isBodyPlaceholder(Tokens $tokens, int $index): bool
    {
        $prevIndex = $tokens->getPrevNonWhitespace($index);
        $nextIndex = $tokens->getNextNonWhitespace($index);

        return $prevIndex !== null
            && $nextIndex !== null
            && $tokens[$prevIndex]->equals('{')
            && $tokens[$nextIndex]->equals('}');
    }

    private function processDocComment(Tokens $tokens, int $index): void
    {
        $content = $tokens[$index]->getContent();

        // Single-line docblock: /** ... */
        if (! str_contains($content, "\n")) {
            if (! str_contains($content, '@')) {
                $this->clearAndCleanWhitespace($tokens, $index);
            }

            return;
        }

        $lines = explode("\n", $content);
        $indent = $this->detectIndent($lines);

        $keptLines = [];
        $braceDepth = 0;

        foreach ($lines as $line) {
            if ($braceDepth > 0) {
                $keptLines[] = $line;
                $braceDepth += substr_count($line, '{') - substr_count($line, '}');
            } elseif (str_contains($line, '@')) {
                $keptLines[] = $line;
                $braceDepth += substr_count($line, '{') - substr_count($line, '}');
            }
        }

        if ($keptLines === []) {
            $this->clearAndCleanWhitespace($tokens, $index);

            return;
        }

        $rebuilt = "/**\n";

        foreach ($keptLines as $line) {
            $rebuilt .= $line."\n";
        }

        $rebuilt .= $indent.'*/';

        if ($rebuilt !== $content) {
            $tokens[$index] = new Token([\T_DOC_COMMENT, $rebuilt]);
        }
    }

    private function clearAndCleanWhitespace(Tokens $tokens, int $index): void
    {
        $prevIndex = $tokens->getPrevNonWhitespace($index);
        $nextIndex = $tokens->getNextNonWhitespace($index);

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);

        if ($prevIndex === null || $nextIndex === null) {
            return;
        }

        // Find the whitespace token that remains after clearing
        for ($i = $prevIndex + 1; $i < $nextIndex; $i++) {
            if ($tokens[$i]->isWhitespace()) {
                $ws = $tokens[$i]->getContent();
                $newlineCount = substr_count($ws, "\n");

                if ($newlineCount > 1) {
                    // Collapse to a single newline + the indent from the last line
                    $lastNewline = strrpos($ws, "\n");
                    $indent = substr($ws, $lastNewline);
                    $tokens[$i] = new Token([T_WHITESPACE, $indent]);
                }

                break;
            }
        }
    }

    private function detectIndent(array $lines): string
    {
        foreach ($lines as $line) {
            if (preg_match('/^(\s+)\*/', $line, $matches)) {
                return $matches[1];
            }
        }

        return ' ';
    }
}
