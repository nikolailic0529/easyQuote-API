<?php

namespace App\Domain\QuoteFile\Concerns;

use App\Domain\QuoteFile\Models\QuoteFile;
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
