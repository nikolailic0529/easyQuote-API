<?php

namespace App\Contracts;

use App\Models\Opportunity;

interface WithOpportunityEntity
{
    public function getOpportunity(): Opportunity;
}
