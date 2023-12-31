<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

final class UpdateDraftFieldInput extends BaseInput
{
    public function __construct(
        public readonly string $id,
        public readonly InputValueEnum|string|null $defaultValue = InputValueEnum::Miss,
        public readonly InputValueEnum|FieldDataSetItemCollection|null $dataSet = InputValueEnum::Miss
    ) {
    }
}
