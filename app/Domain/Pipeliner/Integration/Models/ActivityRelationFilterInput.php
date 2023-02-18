<?php

namespace App\Domain\Pipeliner\Integration\Models;

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
