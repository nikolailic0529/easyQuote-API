<?php

namespace App\Traits;

use App\Models\QuoteFile\QuoteFile;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuoteFiles
{
    public function quoteFiles(): HasMany
    {
        return $this->hasMany(QuoteFile::class);
    }
}
