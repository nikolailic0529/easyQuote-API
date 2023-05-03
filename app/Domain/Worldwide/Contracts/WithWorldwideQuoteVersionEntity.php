<?php

namespace App\Domain\Worldwide\Contracts;

use App\Domain\Worldwide\Models\WorldwideQuoteVersion;

interface WithWorldwideQuoteVersionEntity
{
    public function getQuoteVersion(): WorldwideQuoteVersion;
}
