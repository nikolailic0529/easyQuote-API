<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\CloudObjectTypeEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;

final class CreateCloudObjectInput extends BaseInput
{
    public function __construct(
        public readonly string $filename,
        public readonly CloudObjectTypeEnum $type,
        public readonly InputValueEnum|string $url = InputValueEnum::Miss,
        public readonly InputValueEnum|string $content = InputValueEnum::Miss
    ) {
    }
}