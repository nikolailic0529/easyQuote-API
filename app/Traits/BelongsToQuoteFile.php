<?php

namespace App\Traits;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToQuoteFile
{
    public function quoteFile(): BelongsTo
    {
        return $this->belongsTo(QuoteFile::class);
    }
}
