<?php

namespace App\Contracts;

use App\Models\Quote\Quote;

interface WithRescueQuoteEntity
{
    public function getQuote(): Quote;
}
