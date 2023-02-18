<?php

namespace App\Domain\Pipeliner\Integration\Models;

class UpdateOpportunityInputCollection extends BaseInputCollection
{
    public function current(): UpdateOpportunityInput
    {
        return parent::current();
    }
}
