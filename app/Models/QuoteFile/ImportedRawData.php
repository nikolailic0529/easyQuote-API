<?php

namespace App\Models\QuoteFile;

use App\Models\UuidModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\{
    Draftable,
    BelongsToQuoteFile,
    BelongsToUser
};

class ImportedRawData extends UuidModel
{
    use BelongsToUser, BelongsToQuoteFile, Draftable, SoftDeletes;

    protected $fillable = [
        'page', 'file_path'
    ];
}
