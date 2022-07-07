<?php

namespace App\Integrations\Pipeliner\Models;

class ContactAccountRelationFilterInput extends BaseFilterInput
{
    public function accountId(EntityFilterStringField $field)
    {
        return $this->setField(__FUNCTION__, $field);
    }
}