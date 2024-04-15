<?php

namespace App\Fixers\LaravelBlade;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class Fixer extends AbstractFixer
{
    /**
     * The list of ignorables.
     *
     * @var array<int, string>
     */
    protected static $ignorables = [
        Ignorables\Envoy::class,
        Ignorables\MarkdownMail::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(protected $formatter)
    {
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

        foreach (static::$ignorables as $ignorable) {
            if (app()->call($ignorable, [
                'path' => $path,
                'content' => $content,
            ])) {
                return;
            }
        }

        $content = $this->formatter->format($path, $content);

        $tokens->setCode($content);
    }
}
