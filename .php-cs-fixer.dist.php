<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => false,
        'phpdoc_to_comment' => false,
        'no_extra_blank_lines' => true,
        'blank_line_after_opening_tag' => false,
        'phpdoc_no_package' => false,
    ])
    ->setFinder($finder)
;
