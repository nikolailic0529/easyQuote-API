<?php

namespace App\Models\QuoteFile;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\{
    BelongsToQuoteFile,
    BelongsToUser,
    Uuid
};
use Illuminate\Database\Eloquent\Model;

class ImportedRawData extends Model
{
    use Uuid, BelongsToUser, BelongsToQuoteFile, SoftDeletes;

    protected $fillable = [
        'page', 'file_path'
    ];
}
