<?php

namespace App\Domain\CustomField\Services\Calc;

use Spatie\DataTransferObject\DataTransferObject;

class CustomFieldEvaluationResult extends DataTransferObject
{
    public $result;
    public ?array $errors;
}
