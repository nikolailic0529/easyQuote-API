<?php

namespace App\Integrations\Pipeliner\Models;

class OpportunityFilterInput extends BaseFilterInput
{
    public function name(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function pipelineId(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function accountRelations(LeadOpptyAccountRelationFilterInput $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function contactRelations(LeadOpptyContactRelationFilterInput $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }
}