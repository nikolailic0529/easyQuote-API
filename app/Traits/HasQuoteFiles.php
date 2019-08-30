<?php

namespace App\Traits;

use App\Models\QuoteFile\QuoteFile;

trait HasQuoteFiles
{
    public function quoteFiles()
    {
        return $this->hasMany(QuoteFile::class);
    }
}