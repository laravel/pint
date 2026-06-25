<?php

namespace App\Fixers\LaravelBlade;

use App\BladeFormatter;
use App\Contracts\HasPrettierDependencies;
use App\Exceptions\PrettierException;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class Fixer extends AbstractFixer implements HasPrettierDependencies
{
    /**
     * The list of ignorables.
     *
     * @var array<int, string>
     */
    protected static $ignorables = [
        Ignorables\Envoy::class,
        Ignorables\BoostGuidelines::class,
        Ignorables\EmailView::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(protected BladeFormatter $formatter) {}

    /**
     * {@inheritdoc}
     */
    public function prettierDependencies(): array
    {
        return [
            'prettier' => '^3.8.4',
            'prettier-plugin-blade' => '^3.2.2',
            'prettier-plugin-tailwindcss' => '^0.8.0',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Pint/laravel_blade';
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
     * @throws PrettierException
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
}
