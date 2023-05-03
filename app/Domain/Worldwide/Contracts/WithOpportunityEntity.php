<?php

namespace App\Domain\Worldwide\Contracts;

use App\Domain\Worldwide\Models\Opportunity;

interface WithOpportunityEntity
{
    public function getOpportunity(): Opportunity;
}
