<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportedRawData,
    QuoteFile\ImportedColumnData,
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
        'original_file_path',
        'file_type'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function importedRawData()
    {
        return $this->hasMany(ImportedRawData::class);
    }

    public function columnData()
    {
        return $this->hasMany(ImportedColumnData::class);
    }

    public function dataSelectSeparator()
    {
        return $this->belongsTo(DataSelectSeparator::class);
    }
}
