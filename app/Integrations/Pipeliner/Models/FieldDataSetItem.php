<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class FieldDataSetItem extends BaseInput
{
    public function __construct(public readonly string      $optionName,
                                public readonly float       $calcValue,
                                public readonly string|null $id = null,
                                public readonly InputValueEnum|array $allowedBy = InputValueEnum::Miss)
    {
    }
}