<?php

namespace App\Domain\Pipeliner\Integration\Models;

class AccountSharingClientRelationFilterInput extends BaseFilterInput
{
    public function accountId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function clientId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}
