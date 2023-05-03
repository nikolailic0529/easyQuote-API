<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'bootstrap/cache',
        'storage',
        'resources',
        'public',
        'stubs',
        'vendor',
    ])
    ->notPath([
        'app/Domain/Company/DataTransferObjects/CreateCompanyData.php',
        'app/Domain/Company/DataTransferObjects/UpdateCompanyData.php',
        'app/Domain/Worldwide/DataTransferObjects/Opportunity/UpdateOpportunityData.php',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'visibility_required' => ['elements' => ['method', 'property']],
        'void_return' => true,
        'static_lambda' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
