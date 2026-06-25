<?php

namespace App\Support;

use App\Repositories\ConfigurationJsonRepository;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;

class PhpFragmentFormatter
{
    /**
     * Fixers that are unsafe on an embedded fragment and skipped for every run.
     *
     * @var array<int, string>
     */
    private const DENYLIST = ['no_closing_tag', 'Pint/laravel_blade'];

    /**
     * Fixers skipped only for stripped fragments, where they corrupt the output.
     *
     * @var array<int, string>
     */
    private const FRAGMENT_DENYLIST = ['fully_qualified_strict_types', 'no_unused_imports', 'declare_strict_types'];

    /**
     * The priority-sorted PHP fixers, memoized per fragment/file key.
     *
     * @var array<string, array<int, FixerInterface>>
     */
    private array $fixers = [];

    /**
     * A stand-in file used for the fixers' "supports()" checks.
     */
    private SplFileInfo $file;

    public function __construct()
    {
        $this->file = new SplFileInfo('fragment.php');
    }

    /**
     * Format a PHP document that already opens with "<?php".
     */
    public function format(string $code, bool $fragment = false): string
    {
        try {
            $tokens = Tokens::fromCode($code);
        } catch (\CompileError) {
            // Not a syntactically-complete PHP document on its own; leave it as-is.
            return $code;
        }

        foreach ($this->fixers($fragment) as $fixer) {
            if (! $fixer->supports($this->file) || ! $fixer->isCandidate($tokens)) {
                continue;
            }

            $fixer->fix($this->file, $tokens);

            if ($tokens->isChanged()) {
                $tokens->clearEmptyTokens();
                $tokens->clearChanged();
            }
        }

        return $tokens->generateCode();
    }

    /**
     * Resolve (and memoize) the fixers for the active preset.
     *
     * @return array<int, FixerInterface>
     */
    private function fixers(bool $fragment): array
    {
        $key = $fragment ? 'fragment' : 'file';

        if (isset($this->fixers[$key])) {
            return $this->fixers[$key];
        }

        $rules = $this->rules();

        $deny = $fragment ? array_merge(self::DENYLIST, self::FRAGMENT_DENYLIST) : self::DENYLIST;

        foreach ($deny as $name) {
            unset($rules[$name]);
        }

        $factory = new FixerFactory;
        $factory->registerBuiltInFixers();
        $factory->useRuleSet(new RuleSet($rules));
        $factory->setWhitespacesConfig(new WhitespacesFixerConfig('    ', "\n"));

        return $this->fixers[$key] = $factory->getFixers();
    }

    /**
     * The rules Pint is applying to ".php" files in this run.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        if (($rules = $this->rulesFromArgv()) !== null) {
            return $rules;
        }

        $preset = resolve(ConfigurationJsonRepository::class)->preset();

        $config = require implode(DIRECTORY_SEPARATOR, [
            dirname(__DIR__, 2), 'resources', 'presets', "{$preset}.php",
        ]);

        return $config->getRules();
    }

    /**
     * The ruleset php-cs-fixer passes to a parallel worker via "--rules", or null.
     *
     * @return array<string, mixed>|null
     */
    private function rulesFromArgv(): ?array
    {
        $argv = $_SERVER['argv'] ?? [];

        foreach ($argv as $i => $arg) {
            if ($arg === '--rules') {
                $json = $argv[$i + 1] ?? null;
            } elseif (str_starts_with($arg, '--rules=')) {
                $json = substr($arg, strlen('--rules='));
            } else {
                continue;
            }

            $rules = is_string($json) ? json_decode($json, true) : null;

            return is_array($rules) ? $rules : null;
        }

        return null;
    }
}
