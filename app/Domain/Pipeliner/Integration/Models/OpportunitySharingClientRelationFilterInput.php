<?php

namespace App\Domain\Pipeliner\Integration\Models;

class OpportunitySharingClientRelationFilterInput extends BaseFilterInput
{
    public function leadOpptyId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function clientId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}
