<?php

namespace App\Actions;

use App\Factories\ConfigurationResolverFactory;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class MakeConfiguration
{
    public function execute(): array
    {
        $preset = select(
            label: 'Choose a pint preset',
            options: ConfigurationResolverFactory::$presets,
        );

        $selectedRules = multiselect(
            label: 'Select the rules to enable',
            options: $this->getAvailableRules(),
        );

        $rules = $this->formatRules($selectedRules);

        $config = [
            'preset' => $preset,
            'rules' => $rules,
        ];

        $this->saveConfig($config);

        \Laravel\Prompts\info('Generated pint.json file!');

        return [0, []];
    }

    /**
     * Format the config in a pretty json and save pint.json
     */
    private function saveConfig(array $config): void
    {
        $configJsonPath = base_path('pint.json');

        File::put($configJsonPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Format rules in a key => enabled format
     *
     * @return array<string, bool>
     */
    private function formatRules(array $selectedRules): array
    {
        $availableRules = $this->getAvailableRules();

        $rules = array_map(
            fn ($rule) => in_array($rule, $selectedRules),
            array_keys($availableRules),
        );

        return array_combine(
            array_keys($availableRules),
            $rules,
        );
    }

    private function getAvailableRules(): array
    {
        return [
            'array_push' => 'array_push - Converts `array_push($array, $x);` to `$array[] = $x;`.',
            'backtick_to_shell_exec' => 'backtick_to_shell_exec - Replaces backtick operators with `shell_exec()` calls.',
            'date_time_immutable' => 'date_time_immutable - Replaces `DateTime` instances with `DateTimeImmutable`.',
            'declare_strict_types' => 'declare_strict_types - Ensures `declare(strict_types=1);` is present at the beginning of PHP files.',
            'lowercase_keywords' => 'lowercase_keywords - Enforces PHP keywords to be in lowercase.',
            'lowercase_static_reference' => 'lowercase_static_reference - Ensures `self`, `static`, and `parent` are in lowercase.',
            'final_class' => 'final_class - Adds `final` modifier to all classes that are not abstract and not already final.',
            'final_internal_class' => 'final_internal_class - Marks internal classes as `final`.',
            'final_public_method_for_abstract_class' => 'final_public_method_for_abstract_class - Marks public methods of abstract classes as `final`.',
            'fully_qualified_strict_types' => 'fully_qualified_strict_types - Ensures all types in `declare(strict_types=1);` are fully qualified.',
            'global_namespace_import' => 'global_namespace_import - Imports global classes, functions, and constants into the global namespace.',
            'mb_str_functions' => 'mb_str_functions - Replaces non-multibyte-safe string functions with their `mb_*` equivalents.',
            'modernize_types_casting' => 'modernize_types_casting - Replaces traditional type casts with modern equivalents.',
            'new_with_parentheses' => 'new_with_parentheses - Ensures that all instances created with `new` are followed by parentheses.',
            'no_superfluous_elseif' => 'no_superfluous_elseif - Replaces unnecessary `elseif` constructs with `if`.',
            'no_useless_else' => 'no_useless_else - Removes `else` blocks that are not necessary.',
            'no_multiple_statements_per_line' => 'no_multiple_statements_per_line - Ensures that there is only one statement per line.',
            'ordered_interfaces' => 'ordered_interfaces - Orders interfaces in `implements` declarations alphabetically.',
            'ordered_traits' => 'ordered_traits - Orders traits in `use` declarations alphabetically.',
            'protected_to_private' => 'protected_to_private - Converts `protected` properties and methods to `private` when possible.',
            'self_accessor' => 'self_accessor - Replaces `$this` with `self::` when accessing static members.',
            'self_static_accessor' => 'self_static_accessor - Ensures static methods are called using `self::`.',
            'strict_comparison' => 'strict_comparison - Enforces the use of strict comparisons (`===` and `!==`).',
            'visibility_required' => 'visibility_required - Ensures that all class members have explicit visibility modifiers.',
        ];
    }
}
