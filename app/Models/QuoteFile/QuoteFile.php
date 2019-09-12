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
    BelongsToQuote,
    HasFileFormat,
    Draftable,
    Handleable,
    HasColumnsData
};
use App\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteFile extends UuidModel implements HasOrderedScope
{
    use HasColumnsData, BelongsToQuote, BelongsToUser, HasFileFormat, Handleable, Draftable, SoftDeletes;
    
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

    public function importedRawData()
    {
        return $this->hasMany(ImportedRawData::class);
    }

    public function dataSelectSeparator()
    {
        return $this->belongsTo(DataSelectSeparator::class);
    }

    public function isWord()
    {
        if(is_null($this->format)) {
            return false;
        }
        
        $extension = $this->format->extension;

        return in_array($extension, ['doc', 'docx']);
    }

    public function isPdf()
    {
        if(is_null($this->format)) {
            return false;
        }
        
        $extension = $this->format->extension;

        return in_array($extension, ['pdf']);
    }

    public function isCsv()
    {
        if(is_null($this->format)) {
            return false;
        }
        
        $extension = $this->format->extension;

        return in_array($extension, ['csv']);
    }

    public function isNewSeparator(string $id)
    {
        if(is_null($this->dataSelectSeparator)) {
            return false;
        }

        return $this->dataSelectSeparator->id !== $id;
    }
}
