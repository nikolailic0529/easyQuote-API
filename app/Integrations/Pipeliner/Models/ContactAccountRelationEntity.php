<?php

namespace App\Integrations\Pipeliner\Models;

class ContactAccountRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $accountId,
        public readonly bool $isPrimary,
        public readonly bool $isAssistant,
        public readonly bool $isSibling
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            accountId: $array['accountId'],
            isPrimary: $array['isPrimary'],
            isAssistant: $array['isAssistant'],
            isSibling: $array['isSibling'],
        );
    }
}