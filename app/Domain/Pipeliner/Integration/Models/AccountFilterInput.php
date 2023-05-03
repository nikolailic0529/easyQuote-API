<?php

namespace App\Domain\Pipeliner\Integration\Models;

class AccountFilterInput extends BaseFilterInput
{
    public function name(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function unitId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function unit(SalesUnitFilterInput $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function relatedEntities(EntityFilterRelatedField $field, EntityFilterRelatedField ...$fields): static
    {
        return $this->setField(__FUNCTION__, FilterInputCollection::from($field, ...$fields));
    }
}
