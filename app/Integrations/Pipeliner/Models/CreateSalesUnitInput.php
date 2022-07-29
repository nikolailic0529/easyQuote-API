<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class CreateSalesUnitInput extends BaseInput
{
    public function __construct(public readonly string                $name,
                                public readonly string|InputValueEnum $parentId = InputValueEnum::Miss)
    {
    }
}