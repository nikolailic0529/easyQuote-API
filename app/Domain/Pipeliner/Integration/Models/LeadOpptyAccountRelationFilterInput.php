<?php

namespace App\Domain\Pipeliner\Integration\Models;

class LeadOpptyAccountRelationFilterInput extends BaseFilterInput
{
    public function accountId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}
