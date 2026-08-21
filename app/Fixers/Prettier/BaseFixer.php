<?php

namespace App\Fixers\Prettier;

use App\Contracts\HasFinderNames;
use App\Contracts\HasPrettierDependencies;
use App\Exceptions\PrettierException;
use App\Support\Prettier;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

abstract class BaseFixer extends AbstractFixer implements HasFinderNames, HasPrettierDependencies
{
    /**
     * Create a new prettier fixer instance.
     */
    public function __construct(protected Prettier $prettier) {}

    /**
     * {@inheritdoc}
     */
    public function prettierDependencies(): array
    {
        return [
            'prettier' => '^3.8.4',
        ];
    }

    /**
     * The prettier parser each supported file extension is formatted with.
     *
     * @return array<string, string>
     */
    abstract protected function parsers(): array;

    /**
     * {@inheritdoc}
     */
    public function finderNames(): array
    {
        return array_map(
            fn (string $extension): string => '*.'.$extension,
            array_keys($this->parsers()),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return true;
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
     *
     * @throws PrettierException
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        // The real path is preferred, but a file that cannot be resolved on
        // disk, e.g. one handed over through stdin, still keeps its name.
        $path = $file->getRealPath() ?: $file->getPathname();

        if (($parser = $this->parserFor($path)) === null) {
            return;
        }

        $content = $this->prettier->format($path, $tokens->generateCode(), [
            'parser' => $parser,
        ]);

        // Tokens::setCode() rejects an empty string, so clear the tokens directly.
        if ($content === '') {
            foreach ($tokens as $index => $token) {
                $tokens->clearAt($index);
            }

            $tokens->clearEmptyTokens();

            return;
        }

        $tokens->setCode($content);
    }

    /**
     * The prettier parser for the given path, or null when the fixer does
     * not format it.
     */
    protected function parserFor(string $path): ?string
    {
        $fileName = basename(str_replace('\\', '/', $path));

        // Minified assets are machine-generated; reformatting them only bloats diffs.
        if (str_contains($fileName, '.min.')) {
            return null;
        }

        foreach ($this->parsers() as $extension => $parser) {
            if (str_ends_with($fileName, '.'.$extension)) {
                return $parser;
            }
        }

        return null;
    }
}
