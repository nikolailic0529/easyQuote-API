<?php

namespace App\Integrations\Pipeliner\Models;

class ActivityRelationFilterInput extends BaseFilterInput
{
    public function id(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function leadOpptyId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function accountId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}