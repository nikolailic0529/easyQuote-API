<?php

namespace App\Domain\CustomField\Services\Calc;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CustomFieldExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('empty',
                compiler: static function (...$args): string {
                    return sprintf('%s(%s)', 'empty', implode(', ', $args));
                },
                evaluator: static function ($p, ...$args): bool {
                    return empty($args[0]);
                },
            ),
        ];
    }
}
