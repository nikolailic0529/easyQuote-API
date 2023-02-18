<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Attributes\SerializeWith;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Serializers\EnumListSerializer;

final class CreateWebhookInput extends BaseInput
{
    public function __construct(
        public readonly string $url,
        #[SerializeWith(EnumListSerializer::class)] public readonly array $events,
        public readonly bool $insecureSsl,
        public readonly InputValueEnum|string $signature = InputValueEnum::Miss,
        public readonly InputValueEnum|string $options = InputValueEnum::Miss,
    ) {
    }
}
