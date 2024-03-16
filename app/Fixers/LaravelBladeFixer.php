<?php

namespace App\Fixers;

use App\Exceptions\PrettierException;
use App\Prettier;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class LaravelBladeFixer extends AbstractFixer
{
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

        /** @var \Illuminate\Process\ProcessResult $result */
        $result = app(Prettier::class)->run([$path]);

        if ($result->failed()) {
            $error = $result->errorOutput();

            if (str($error)->startsWith('[error]') && str($error)->contains('SyntaxError:')) {
                $error = str($error)->after('SyntaxError: ')->before("\n")->value();
            }

            throw new PrettierException($error);
        }

        $tokens->setCode($result->output());
    }
}
