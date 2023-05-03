<?php

namespace App\Domain\Pipeliner\Integration\Models;

class ContactAccountRelationFilterInput extends BaseFilterInput
{
    public function accountId(EntityFilterStringField $field)
    {
        return $this->setField(__FUNCTION__, $field);
    }
}
