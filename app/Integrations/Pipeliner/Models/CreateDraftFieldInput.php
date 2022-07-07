<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class CreateDraftFieldInput extends BaseInput
{
    public function __construct(public readonly string                                         $entityName,
                                public readonly string                                         $name,
                                public readonly string                                         $typeId,
                                public readonly InputValueEnum|string|null                     $defaultValue = InputValueEnum::Miss,
                                public readonly InputValueEnum|FieldDataSetItemCollection|null $dataSet = InputValueEnum::Miss)
    {
    }
}