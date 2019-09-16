<?php namespace App\Traits;

use App\Models\QuoteFile\ImportedRow;

trait BelongsToImportedRow
{
    public function importedRow()
    {
        return $this->belongsTo(ImportedRow::class);
    }
}
