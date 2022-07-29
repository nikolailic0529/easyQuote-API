<?php

namespace App\Integrations\Pipeliner\Models;

class NoteFilterInput extends BaseFilterInput
{
    public function leadOpptyId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function accountId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}