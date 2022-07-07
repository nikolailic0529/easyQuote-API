<?php

namespace App\Integrations\Pipeliner\Models;

class LeadOpptyContactRelationFilterInput extends BaseFilterInput
{
    public function contactId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}