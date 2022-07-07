<?php

namespace App\Integrations\Pipeliner\Models;

class AppointmentFilterInput extends BaseFilterInput
{
    public function opportunityRelations(ActivityRelationFilterInput $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}