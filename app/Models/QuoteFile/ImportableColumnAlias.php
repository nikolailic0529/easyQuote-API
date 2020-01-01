<?php

namespace App\Models\QuoteFile;

use App\Models\BaseModel;
use App\Traits\{
    BelongsToImportableColumn
};

class ImportableColumnAlias extends BaseModel
{
    use BelongsToImportableColumn;

    public $timestamps = false;

    protected $fillable = [
        'alias'
    ];
}
