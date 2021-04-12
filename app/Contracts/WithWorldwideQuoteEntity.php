<?php

namespace App\Contracts;

use App\Models\Quote\WorldwideQuote;

interface WithWorldwideQuoteEntity
{
    public function getQuote(): WorldwideQuote;
}
