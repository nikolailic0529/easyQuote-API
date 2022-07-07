<?php

namespace App\Integrations\Pipeliner\Models;

class UpdateOpportunityInputCollection extends BaseInputCollection
{
    public function current(): UpdateOpportunityInput
    {
        return parent::current();
    }
}