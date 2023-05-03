<?php

namespace App\Domain\CustomField\Services\Calc;

use App\Domain\CustomField\Models\CustomField;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class CustomFieldEvaluationService
{
    public function __construct(protected CustomFieldExpressionLanguage $expressionLanguage)
    {
    }

    public function evaluate(CustomField $field, array $variables = []): mixed
    {
        if (null === $field->calc_formula) {
            throw new \InvalidArgumentException("The custom field `$field->field_name` doesn't support calculation.");
        }

        $result = [
            'result' => null,
            'errors' => [],
        ];

        try {
            $result['result'] = $this->expressionLanguage->evaluate($field->calc_formula, $variables);
        } catch (SyntaxError $e) {
            $result['errors'] = [$this->interpretError($e)];
        }

        if (count($result['errors']) === 0) {
            $result['errors'] = null;
        }

        return new CustomFieldEvaluationResult($result);
    }

    private function interpretError(\Throwable $e): string
    {
        if ($e instanceof SyntaxError) {
            $pattern = '#variable "(?<var>\w+)" is not valid around position \d+ for expression#i';

            if (preg_match($pattern, $e->getMessage(), $matches)) {
                return sprintf('Variable `%s` must be provided to calculate the formula.', $matches['var']);
            }
        }

        return $e->getMessage();
    }
}
