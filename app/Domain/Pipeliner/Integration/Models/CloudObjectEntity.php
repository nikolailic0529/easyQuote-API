<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\CloudObjectTypeEnum;
use App\Foundation\Support\Enum\EnumResolver;
use Illuminate\Support\Carbon;

class CloudObjectEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $filename,
        public readonly bool $isPublic,
        public readonly string $mimeType,
        public readonly string $params,
        public readonly int $size,
        public readonly CloudObjectTypeEnum $type,
        public readonly ?ClientEntity $creator,
        public readonly string $url,
        public readonly ?string $publicUrl,
        public readonly \DateTimeImmutable $created,
        public readonly \DateTimeImmutable $modified
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            filename: $array['filename'],
            isPublic: $array['isPublic'],
            mimeType: $array['mimeType'],
            params: $array['params'],
            size: $array['size'],
            type: EnumResolver::fromKey(CloudObjectTypeEnum::class, $array['type']),
            creator: ClientEntity::tryFromArray($array['creator']),
            url: $array['url'],
            publicUrl: $array['publicUrl'],
            created: Carbon::parse($array['created'])->toDateTimeImmutable(),
            modified: Carbon::parse($array['modified'])->toDateTimeImmutable(),
        );
    }
}
