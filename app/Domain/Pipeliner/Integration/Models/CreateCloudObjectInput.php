<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\CloudObjectTypeEnum;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

final class CreateCloudObjectInput extends BaseInput
{
    public function __construct(
        public readonly string $filename,
        public readonly CloudObjectTypeEnum $type,
        public readonly InputValueEnum|string $url = InputValueEnum::Miss,
        public readonly InputValueEnum|string $content = InputValueEnum::Miss,
        public readonly InputValueEnum|string $creatorId = InputValueEnum::Miss,
    ) {
    }
}
