<?php namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;

trait BelongsToImportableColumn
{
    public function importableColumn()
    {
        return $this->belongsTo(ImportableColumn::class);
    }
}
