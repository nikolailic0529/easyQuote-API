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
        'user', 'user_id', 'quote_file_id', 'quote_file', 'page', 'created_at', 'updated_at', 'drafted_at', 'deleted_at',
        'unknown_header', 'importableColumn', 'imported_row_id'
    ];

    protected $appends = [
        'header'
    ];

    public function getHeaderAttribute()
    {
        if(is_null($this->importableColumn)) {
            return $this->getAttribute('unknown_header') ?: __('Unknown header');
        }

        return $this->importableColumn->header;
    }
}
