<?php

namespace App\Contracts;

use App\Models\Quote\WorldwideQuoteVersion;

interface WithWorldwideQuoteVersionEntity
{
    public function getQuoteVersion(): WorldwideQuoteVersion;
}
