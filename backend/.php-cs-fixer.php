<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/migrations',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,

        // Arrays
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'no_trailing_comma_in_singleline' => true,

        // Imports
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],

        // PHP 8 features
        'declare_strict_types' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'modernize_types_casting' => true,
        'no_useless_nullsafe_operator' => true,

        // Strings
        'single_quote' => true,
        'explicit_string_variable' => true,

        // Doc blocks
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],

        // Whitespace
        'blank_line_before_statement' => ['statements' => ['return', 'throw', 'try', 'if', 'foreach', 'for', 'while']],
        'method_chaining_indentation' => true,

        // Class elements
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],

        // Misc
        'concat_space' => ['spacing' => 'one'],
        'yoda_style' => false,
        'is_null' => true,
        'native_function_casing' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache');
