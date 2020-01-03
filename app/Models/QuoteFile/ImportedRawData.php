<?php

namespace App\Models\QuoteFile;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\{
    Draftable,
    BelongsToQuoteFile,
    BelongsToUser
};

class ImportedRawData extends BaseModel
{
    use BelongsToUser, BelongsToQuoteFile, SoftDeletes;

    protected $fillable = [
        'page', 'file_path'
    ];
}
