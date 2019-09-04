<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportedRawData,
    QuoteFile\ImportedRow,
    QuoteFile\ImportedColumn,
    QuoteFile\DataSelectSeparator
};
use App\Traits \ {
    BelongsToUser,
    HasFileFormat,
    Draftable,
    Handleable
};
use App\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteFile extends UuidModel implements HasOrderedScope
{
    use BelongsToUser, HasFileFormat, Handleable, Draftable, SoftDeletes;
    
    protected $fillable = [
        'original_file_path', 'file_type', 'pages'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function rowsData()
    {
        return $this->hasMany(ImportedRow::class);
    }

    public function columnsData()
    {
        return $this->hasMany(ImportedColumn::class);
    }

    public function importedRawData()
    {
        return $this->hasMany(ImportedRawData::class);
    }

    public function dataSelectSeparator()
    {
        return $this->belongsTo(DataSelectSeparator::class);
    }
}
