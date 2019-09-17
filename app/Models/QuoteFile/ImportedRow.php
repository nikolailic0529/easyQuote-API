<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportedColumn
};
use App\Traits \ {
    BelongsToUser,
    BelongsToQuoteFile,
    HasColumnsData,
    Draftable,
    Selectable
};

class ImportedRow extends UuidModel
{
    use HasColumnsData, BelongsToUser, BelongsToQuoteFile, Draftable, Selectable;

    protected $fillable = [
        'page'
    ];

    protected $hidden = [
        'quote_file', 'user', 'quote_file_id', 'user_id', 'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'page',
        'laravel_through_key'
    ];
}
