<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumn,
    QuoteFile\ImpotedRow
};
use App\Traits \ {
    BelongsToUser,
    BelongsToQuoteFile,
    Draftable
};

class ImportedColumn extends UuidModel
{
    use BelongsToUser, BelongsToQuoteFile, Draftable;
    
    protected $fillable = [
        'value', 'page'
    ];

    protected $hidden = [
        'user', 'user_id', 'quote_file_id', 'quote_file', 'page', 'created_at', 'updated_at', 'drafted_at', 'deleted_at'
    ];

    protected $appends = [
        'header'
    ];

    public function importableColumn()
    {
        return $this->belongsTo(ImportableColumn::class);
    }

    public function importedRow()
    {
        return $this->belongsTo(ImportedRow::class);
    }

    public function getHeaderAttribute()
    {
        return $this->importableColumn->header ?: __('Unknown header');
    }
}
