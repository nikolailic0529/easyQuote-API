<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportedColumn
};
use App\Traits \ {
    BelongsToUser,
    BelongsToQuoteFile,
    Draftable,
    HasColumnsData
};

class ImportedRow extends UuidModel
{
    use HasColumnsData, BelongsToUser, BelongsToQuoteFile, Draftable;

    protected $fillable = [
        'page'
    ];

    protected $hidden = [
        'quote_file', 'user', 'quote_file_id', 'user_id', 'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'page'
    ];
}
