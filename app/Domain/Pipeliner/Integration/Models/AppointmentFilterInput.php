<?php

namespace App\Domain\Pipeliner\Integration\Models;

class AppointmentFilterInput extends BaseFilterInput
{
    public function opportunityRelations(ActivityRelationFilterInput $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function accountRelations(ActivityRelationFilterInput $field): static
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
}
