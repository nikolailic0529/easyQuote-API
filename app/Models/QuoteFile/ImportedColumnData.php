<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumn
};
use App\Traits \ {
    BelongsToUser,
    BelongsToQuoteFile,
    Draftable
};

class ImportedColumnData extends UuidModel
{
    use BelongsToUser, BelongsToQuoteFile, Draftable;
    
    protected $fillable = [
        'value', 'page'
    ];

    protected $hidden = [
        'user', 'importableColumn', 'created_at', 'updated_at', 'drafted_at'
    ];

    public function importableColumn()
    {
        return $this->belongsTo(ImportableColumn::class);
    }
}
