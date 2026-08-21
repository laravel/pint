<?php

namespace App\Fixers\Prettier;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;

class CssFixer extends BaseFixer
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Pint/prettier_css';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Formats CSS, SCSS and Less files using Prettier.', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function parsers(): array
    {
        return [
            'css' => 'css',
            'scss' => 'scss',
            'less' => 'less',
        ];
    }
}
