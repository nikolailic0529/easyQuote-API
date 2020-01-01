<?php

namespace App\Traits;

use App\Models\QuoteFile\ImportedRow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToImportedRow
{
    public function importedRow(): BelongsTo
    {
        return $this->belongsTo(ImportedRow::class);
    }
}
