<?php namespace App\Traits;

use App\Models\QuoteFile\ImportedColumn;

trait HasColumnsData
{
    public function columnsData()
    {
        return $this->hasMany(ImportedColumn::class);
    }
}