<?php

namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait BelongsToImportableColumns
{
    public function importableColumn(): BelongsToMany
    {
        return $this->belongsToMany(ImportableColumn::class);
    }
}
