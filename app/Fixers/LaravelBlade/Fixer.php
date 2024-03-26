<?php

namespace App\Fixers\LaravelBlade;

use App\Prettier;
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
     * The list of pre-processors.
     *
     * @var array<int, string>
     */
    protected static $preProcessors = [
        //
    ];

    /**
     * The list of post-processors.
     *
     * @var array<int, string>
     */
    protected static $postProcessors = [
        PostProcessors\OneLinerSvg::class,
    ];

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

        foreach (static::$ignorables as $ignorable) {
            if (app()->call($ignorable, [
                'path' => $path,
                'content' => $content,
            ])) {
                return;
            }
        }

        $content = $this->prettier->format($path);

        foreach (static::$postProcessors as $postProcessor) {
            $content = resolve($postProcessor)->postProcess($content);
        }

        $tokens->setCode($content);
    }
}
