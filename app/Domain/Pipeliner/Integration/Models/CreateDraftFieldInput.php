<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

class CreateDraftFieldInput extends BaseInput
{
    public function __construct(
        public readonly string $entityName,
        public readonly string $name,
        public readonly string $typeId,
        public readonly InputValueEnum|string|null $defaultValue = InputValueEnum::Miss,
        public readonly InputValueEnum|FieldDataSetItemCollection|null $dataSet = InputValueEnum::Miss
    ) {
    }
}
