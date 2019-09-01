<?php namespace App\Models\QuoteFile;

use App\Models\UuidModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits \ {
    Draftable,
    BelongsToQuoteFile
};

class ImportedRawData extends UuidModel
{
    use BelongsToQuoteFile, Draftable, SoftDeletes;

    protected $fillable = [
        'page', 'content'
    ];

    protected $casts = [
        'content' => 'array'
    ];
}
