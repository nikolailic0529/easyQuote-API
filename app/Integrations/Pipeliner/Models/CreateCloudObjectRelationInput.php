<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class CreateCloudObjectRelationInput extends BaseInput
{
    public function __construct(public readonly string|InputValueEnum                 $cloudObjectId = InputValueEnum::Miss,
                                public readonly CreateCloudObjectInput|InputValueEnum $cloudObject = InputValueEnum::Miss)
    {
    }
}