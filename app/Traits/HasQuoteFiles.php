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

    public function resolveQuoteFile(string $type)
    {
        return optional(
            $this->quoteFiles->firstWhere('file_type', $type)
        );
    }
}
