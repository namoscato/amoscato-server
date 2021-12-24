<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('var')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'phpdoc_align' => ['align' => 'left'],
        'declare_strict_types' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
