<?php

namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToImportableColumn
{
    public function importableColumn(): BelongsTo
    {
        return $this->belongsTo(ImportableColumn::class);
    }
}
