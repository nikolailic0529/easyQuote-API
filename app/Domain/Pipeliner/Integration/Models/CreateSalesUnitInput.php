<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

class CreateSalesUnitInput extends BaseInput
{
    public function __construct(
        public readonly string $name,
        public readonly string|InputValueEnum $parentId = InputValueEnum::Miss
    ) {
    }
}
