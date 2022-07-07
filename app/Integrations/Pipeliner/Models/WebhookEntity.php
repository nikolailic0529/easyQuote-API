<?php

namespace App\Integrations\Pipeliner\Models;

class WebhookEntity
{
    public function __construct(public readonly string             $id,
                                public readonly array              $events,
                                public readonly string             $url,
                                public readonly bool               $insecureSsl,
                                public readonly array              $options,
                                public readonly ?string            $signature,
                                public readonly \DateTimeImmutable $created,
                                public readonly \DateTimeImmutable $modified)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            events: $array['events'],
            url: $array['url'],
            insecureSsl: $array['insecureSsl'],
            options: json_decode($array['options'] ?? '{}', true),
            signature: $array['signature'],
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
        );
    }
}