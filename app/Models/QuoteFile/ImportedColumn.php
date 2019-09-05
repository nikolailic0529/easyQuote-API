<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumn,
    QuoteFile\ImpotedRow
};
use App\Traits \ {
    BelongsToUser,
    BelongsToQuoteFile,
    Draftable,
    BelongsToImportedRow,
    BelongsToImportableColumn
};

class ImportedColumn extends UuidModel
{
    use BelongsToImportableColumn, BelongsToImportedRow, BelongsToUser, BelongsToQuoteFile, Draftable;
    
    protected $fillable = [
        'value', 'page'
    ];

    protected $hidden = [
        'user', 'user_id', 'quote_file_id', 'quote_file', 'page', 'created_at', 'updated_at', 'drafted_at', 'deleted_at'
    ];

    protected $appends = [
        'header'
    ];

    public function getHeaderAttribute()
    {
        return $this->importableColumn->header ?: __('Unknown header');
    }
}
