<?php

namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;

trait BelongsToImportableColumns
{
    public function importableColumn()
    {
        return $this->belongsToMany(ImportableColumn::class);
    }
}
