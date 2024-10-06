<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Facades\File;

class ConfigCommand extends Command
{
    protected $signature = 'config';
    protected $description = 'Interactively generate a .pint.json configuration file';

    public function handle()
    {
        $config = [
            'preset' => $this->choice(
                'Select a preset',
                ['laravel', 'psr12', 'symfony'],
                'laravel'
            ),
        ];

        if ($this->confirm('Do you want to customize rules?', false)) {
            $config['rules'] = $this->customizeRules();
        }

        $config['exclude'] = $this->askExcludedPaths();

        $this->writeConfigFile($config);

        $this->info('.pint.json file has been generated successfully!');
    }

    private function customizeRules()
    {
        $rules = [];
        $commonRules = [
            'array_syntax' => ['short_syntax', 'long_syntax'],
            'ordered_imports' => ['sort_algorithm' => ['alpha', 'length', 'none']],
            'no_unused_imports' => true,
            'not_operator_with_successor_space' => true,
            // Add more common rules here
        ];

        while ($this->confirm('Add a custom rule?', false)) {
            $ruleName = $this->choice('Select or enter a rule name', array_merge(array_keys($commonRules), ['custom']));

            if ($ruleName === 'custom') {
                $ruleName = $this->ask('Enter the custom rule name');
            }

            if (isset($commonRules[$ruleName]) && is_array($commonRules[$ruleName])) {
                $ruleValue = $this->choice("Select a value for $ruleName", $commonRules[$ruleName]);
            } elseif (isset($commonRules[$ruleName])) {
                $ruleValue = $commonRules[$ruleName];
            } else {
                $ruleValue = $this->ask("Enter the value for $ruleName");
            }

            $rules[$ruleName] = $ruleValue;
        }
        return $rules;
    }


    private function askExcludedPaths()
    {
        $excludedPaths = [];
        while ($this->confirm('Add a path to exclude?', false)) {
            $excludedPaths[] = $this->ask('Enter the path to exclude');
        }
        return $excludedPaths;
    }

    private function writeConfigFile($config)
    {
        $jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put(getcwd() . '/.pint.json', $jsonContent);
    }
}
