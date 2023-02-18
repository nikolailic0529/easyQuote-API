<?php

namespace App\Domain\Pipeliner\Integration\Models;

class SalesUnitFilterInput extends BaseFilterInput
{
    public function id(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function parentId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function name(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}
