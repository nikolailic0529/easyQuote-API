<?php namespace App\Models\QuoteFile;

use App\Models\UuidModel;

class ImportableColumn extends UuidModel
{
    public function columnData()
    {
        return $this->hasMany(ImportedColumnData::class);
    }
}
