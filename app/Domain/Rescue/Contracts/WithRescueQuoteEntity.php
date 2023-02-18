<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\Models\Quote;

interface WithRescueQuoteEntity
{
    public function getQuote(): Quote;
}
