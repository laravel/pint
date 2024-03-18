<?php

namespace App\Fixers;

use App\Prettier;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class LaravelBladeFixer extends AbstractFixer
{
    /**
     * The Prettier instance.
     *
     * @var \App\Prettier
     */
    protected $prettier;

    /**
     * {@inheritdoc}
     */
    public function __construct(Prettier $prettier)
    {
        $this->prettier = $prettier;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Laravel/blade';
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
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Fixes Laravel Blade files.', []);
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
     * @throws \App\Exceptions\PrettierException
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $path = $file->getRealPath();

        if (! str_ends_with($path, '.blade.php')) {
            return;
        }

        $content = $tokens->generateCode();

        if (str_contains($content, '<x-mail::') || str_contains($content, '@component(\'mail::')) {
            return;
        }

        $tokens->setCode(
            $this->prettier->format($path),
        );
    }
}
