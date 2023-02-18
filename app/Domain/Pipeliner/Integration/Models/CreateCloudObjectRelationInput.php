<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

class CreateCloudObjectRelationInput extends BaseInput
{
    public function __construct(
        public readonly string|InputValueEnum $cloudObjectId = InputValueEnum::Miss,
        public readonly CreateCloudObjectInput|InputValueEnum $cloudObject = InputValueEnum::Miss
    ) {
    }
}
