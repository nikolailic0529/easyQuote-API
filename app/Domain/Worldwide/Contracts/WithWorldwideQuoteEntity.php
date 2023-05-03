<?php

namespace App\Domain\Worldwide\Contracts;

use App\Domain\Worldwide\Models\WorldwideQuote;

interface WithWorldwideQuoteEntity
{
    public function getQuote(): WorldwideQuote;
}
