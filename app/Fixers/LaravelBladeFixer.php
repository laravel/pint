<?php

namespace App\Fixers;

use App\Prettier;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class LaravelBladeFixer extends AbstractFixer implements ConfigurableFixerInterface
{
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
        $result = app(Prettier::class)->run([$path, ...$this->mapConfigurationToCliOptions()]);

        $tokens->setCode($result->output());
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('sortTailwindcssClasses', 'Sort tailwindcss classes.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
            (new FixerOptionBuilder('sortHtmlAttributes', 'Sort html attributes.'))
                ->setAllowedTypes(['string'])
                ->setDefault('none')
                ->getOption(),
        ]);
    }

    /**
     * Maps the configuration to CLI options.
     *
     * @return array<string, string>
     */
    protected function mapConfigurationToCliOptions(): array
    {
        $configuration = $this->configuration;

        return [
            '--sort-tailwindcss-classes' => $configuration['sortTailwindcssClasses'] ? 'true' : 'false',
            '--sort-html-attributes' => $configuration['sortHtmlAttributes'],
        ];
    }
}
