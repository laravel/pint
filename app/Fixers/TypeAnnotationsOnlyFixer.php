<?php

namespace App\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * @implements ConfigurableFixerInterface<array{ignore_single_line_comments?: bool}, array{ignore_single_line_comments: bool}>
 */
class TypeAnnotationsOnlyFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    /** @use ConfigurableFixerTrait<array{ignore_single_line_comments?: bool}, array{ignore_single_line_comments: bool}> */
    use ConfigurableFixerTrait;

    /**
     * Get the name of the fixer.
     */
    public function getName(): string
    {
        return 'Pint/phpdoc_type_annotations_only';
    }

    /**
     * Get the definition of the fixer.
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Remove all comments except those containing `@` annotations.',
            [new CodeSample("<?php\n// This is a comment\n\$x = 1;\n")],
        );
    }

    /**
     * Get the priority of the fixer.
     *
     * Must run before ClassAttributesSeparationFixer (55),
     * NoMultilineWhitespaceAroundDoubleArrowFixer (31),
     * and all other whitespace/spacing fixers.
     */
    public function getPriority(): int
    {
        return 56;
    }

    /**
     * Determine whether the fixer supports the given file.
     */
    public function supports(SplFileInfo $file): bool
    {
        $path = str_replace('\\', '/', $file->getPathname());

        return ! preg_match('#(?:^|/)config/#', $path);
    }

    /**
     * Determine whether the given tokens are candidates for fixing.
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_COMMENT)
            || $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * Apply the fix to the given file.
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = count($tokens) - 1; $index >= 0; $index--) {
            if ($tokens[$index]->isGivenKind(T_COMMENT)) {
                $this->processComment($tokens, $index);
            } elseif ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                $this->processDocComment($tokens, $index);
            }
        }
    }

    /**
     * Process a single-line or block comment token.
     */
    private function processComment(Tokens $tokens, int $index): void
    {
        $content = $tokens[$index]->getContent();

        if (str_contains($content, '@')) {
            return;
        }

        if ($this->isBodyPlaceholder($tokens, $index)) {
            $placeholder = str_starts_with(ltrim($content), '#') ? '#' : '//';

            if (trim($content) !== $placeholder) {
                $tokens[$index] = new Token([T_COMMENT, $placeholder]);
            }

            return;
        }

        if ($this->configuration['ignore_single_line_comments'] && $this->isSingleLineComment($content)) {
            return;
        }

        $this->clearAndCleanWhitespace($tokens, $index);
    }

    /**
     * Determine whether the comment content is a single-line comment.
     */
    private function isSingleLineComment(string $content): bool
    {
        return str_starts_with(ltrim($content), '//') || str_starts_with(ltrim($content), '#');
    }

    /**
     * Create the configuration definition.
     */
    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('ignore_single_line_comments', 'Whether single-line comments (`//` and `#`) should be preserved.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
        ]);
    }

    /**
     * Determine whether the comment is the only statement inside a body.
     */
    private function isBodyPlaceholder(Tokens $tokens, int $index): bool
    {
        $prevIndex = $tokens->getPrevNonWhitespace($index);
        $nextIndex = $tokens->getNextNonWhitespace($index);

        return $prevIndex !== null
            && $nextIndex !== null
            && $tokens[$prevIndex]->equals('{')
            && $tokens[$nextIndex]->equals('}');
    }

    /**
     * Process a docblock comment token.
     */
    private function processDocComment(Tokens $tokens, int $index): void
    {
        $content = $tokens[$index]->getContent();

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
            $tokens[$index] = new Token([T_DOC_COMMENT, $rebuilt]);
        }
    }

    /**
     * Clear the token and clean up surrounding whitespace.
     */
    private function clearAndCleanWhitespace(Tokens $tokens, int $index): void
    {
        $prevIndex = $tokens->getPrevNonWhitespace($index);
        $nextIndex = $tokens->getNextNonWhitespace($index);

        $hadBlankLineBefore = false;

        if ($prevIndex !== null) {
            for ($i = $prevIndex + 1; $i < $index; $i++) {
                if ($tokens[$i]->isWhitespace() && substr_count($tokens[$i]->getContent(), "\n") >= 2) {
                    $hadBlankLineBefore = true;

                    break;
                }
            }
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);

        if ($prevIndex === null || $nextIndex === null) {
            return;
        }

        for ($i = $prevIndex + 1; $i < $nextIndex; $i++) {
            if ($tokens[$i]->isWhitespace()) {
                $ws = $tokens[$i]->getContent();
                $newlineCount = substr_count($ws, "\n");

                if ($newlineCount > 1) {
                    $lastNewline = strrpos($ws, "\n");
                    $indent = substr($ws, $lastNewline);
                    $prefix = $hadBlankLineBefore ? "\n" : '';
                    $tokens[$i] = new Token([T_WHITESPACE, $prefix.$indent]);
                }

                break;
            }
        }
    }

    /**
     * Detect the indentation from docblock lines.
     *
     * @param  array<int, string>  $lines
     */
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
