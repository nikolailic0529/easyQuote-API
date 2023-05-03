<?php

namespace App\Domain\Pipeliner\Integration\Models;

class LeadOpptyContactRelationFilterInput extends BaseFilterInput
{
    public function contactId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}
