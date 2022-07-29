<?php

namespace App\Services\CustomField\Calc;

use Spatie\DataTransferObject\DataTransferObject;

class CustomFieldEvaluationResult extends DataTransferObject
{
    public $result;
    public ?array $errors;
}