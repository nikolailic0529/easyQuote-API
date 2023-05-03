<?php

namespace App\Domain\Pipeliner\Integration\Models;

class FieldFilterInput extends BaseFilterInput
{
    public function entityName(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function apiName(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function name(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function typeId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function OR(FieldFilterInput ...$filters): static
    {
        return $this->setField(__FUNCTION__, FilterInputCollection::from(...$filters));
    }
}
