<?php namespace App\Traits;

use App\Models\QuoteFile\QuoteFile;

trait BelongsToQuoteFile
{
    public function quoteFile()
    {
        return $this->belongsTo(QuoteFile::class);
    }
}
