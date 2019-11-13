<?php

namespace App\Models\QuoteFile;

use App\Models\UuidModel;
use App\Traits\{
    BelongsToImportableColumn
};

class ImportableColumnAlias extends UuidModel
{
    use BelongsToImportableColumn;

    public $timestamps = false;

    protected $fillable = [
        'alias'
    ];
}
