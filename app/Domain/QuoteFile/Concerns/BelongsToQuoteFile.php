<?php

namespace App\Domain\QuoteFile\Concerns;

use App\Domain\QuoteFile\Models\QuoteFile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToQuoteFile
{
    public function quoteFile(): BelongsTo
    {
        return $this->belongsTo(QuoteFile::class);
    }
}
