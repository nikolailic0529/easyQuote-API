<?php

namespace App\Integrations\Pipeliner\Models;

class AccountFilterInput extends BaseFilterInput
{
    public function name(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}