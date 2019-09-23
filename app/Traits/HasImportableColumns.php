<?php namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;

trait HasImportableColumns
{
    public function importableColumns()
    {
        return $this->hasMany(ImportableColumn::class);
    }
}
