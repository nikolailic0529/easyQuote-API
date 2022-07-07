<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Serializers\EnumListSerializer;

final class CreateWebhookInput extends BaseInput
{
    public function __construct(public readonly string                                            $url,
                                #[SerializeWith(EnumListSerializer::class)] public readonly array $events,
                                public readonly bool                                              $insecureSsl,
                                public readonly InputValueEnum|string                             $signature = InputValueEnum::Miss,
                                public readonly InputValueEnum|string                             $options = InputValueEnum::Miss,)
    {
    }
}