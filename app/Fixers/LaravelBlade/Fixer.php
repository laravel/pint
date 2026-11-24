<?php

namespace App\Fixers\LaravelBlade;

use App\BladeFormatter;
use App\Contracts\HasPrettierDependencies;
use App\Exceptions\PrettierException;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * @implements ConfigurableFixerInterface<array{sort_tailwind_classes?: bool}, array{sort_tailwind_classes: bool}>
 */
class Fixer extends AbstractFixer implements ConfigurableFixerInterface, HasPrettierDependencies
{
    /** @use ConfigurableFixerTrait<array{sort_tailwind_classes?: bool}, array{sort_tailwind_classes: bool}> */
    use ConfigurableFixerTrait;

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
    public function __construct(protected BladeFormatter $formatter)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function prettierDependencies(): array
    {
        $dependencies = [
            'prettier' => '^3.9.6',
            'prettier-plugin-blade' => '^3.3.3',
        ];

        if ($this->sortsTailwindClasses()) {
            $dependencies['prettier-plugin-tailwindcss'] = '^0.8.1';
        }

        return $dependencies;
    }

    /**
     * The prettier options overriding the bundled configuration.
     *
     * @return array<string, mixed>
     */
    public function prettierOptions(): array
    {
        if ($this->sortsTailwindClasses()) {
            return [];
        }

        return ['plugins' => ['prettier-plugin-blade']];
    }

    /**
     * Determine whether Tailwind CSS classes are sorted.
     */
    protected function sortsTailwindClasses(): bool
    {
        return $this->configuration['sort_tailwind_classes'] ?? true;
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
     */
    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('sort_tailwind_classes', 'Whether Tailwind CSS classes are sorted using "prettier-plugin-tailwindcss".'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
        ]);
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

        $content = $this->formatter->format($path, $content, $this->prettierOptions());

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
