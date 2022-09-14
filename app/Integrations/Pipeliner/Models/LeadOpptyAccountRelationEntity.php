<?php

namespace App\Integrations\Pipeliner\Models;

class LeadOpptyAccountRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $accountId,
        public readonly bool $isPrimary,
        public readonly AccountEntity $account
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            accountId: $array['accountId'],
            isPrimary: $array['isPrimary'],
            account: AccountEntity::fromArray($array['account'])
        );
    }
}