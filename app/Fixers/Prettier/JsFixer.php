<?php

namespace App\Fixers\Prettier;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;

class JsFixer extends BaseFixer
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Pint/prettier_js';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Formats JavaScript and TypeScript files using Prettier.', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function parsers(): array
    {
        return [
            'js' => 'babel',
            'jsx' => 'babel',
            'mjs' => 'babel',
            'cjs' => 'babel',
            'ts' => 'typescript',
            'tsx' => 'typescript',
        ];
    }
}
