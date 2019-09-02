<?php namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;

trait BelongsToImportableColumn
{
    public function importable_column()
    {
        return $this->belongsTo(ImportableColumn::class);
    }
}