<?php

namespace App\Services\CustomField\Calc;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class CustomFieldExpressionLanguage extends ExpressionLanguage
{
    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        array_unshift($providers, new CustomFieldExpressionLanguageProvider());

        parent::__construct($cache, $providers);
    }
}