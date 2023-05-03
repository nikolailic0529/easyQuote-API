<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

final class CreateDataInput extends BaseInput
{
    public function __construct(
        public readonly string $dataSetId,
        public readonly string $optionName,
        public readonly int $sortOrder,
        public readonly InputValueEnum|float $calcValue = InputValueEnum::Miss
    ) {
    }
}
