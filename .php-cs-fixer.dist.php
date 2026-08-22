<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->exclude([
        'storage',
        'vendor',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP8x4Migration' => true,

        'array_syntax' => [
            'syntax' => 'short',
        ],

        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],

        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,

        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],

        'concat_space' => [
            'spacing' => 'one',
        ],

        'declare_strict_types' => true,

        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],

        'no_unused_imports' => true,

        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],

        'single_quote' => true,

        'trailing_comma_in_multiline' => [
            'elements' => [
                'arguments',
                'arrays',
                'match',
                'parameters',
            ],
        ],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache');
