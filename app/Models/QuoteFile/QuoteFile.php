<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportedRawData,
    QuoteFile\ImportedRow,
    QuoteFile\DataSelectSeparator
};
use App\Traits \ {
    BelongsToUser,
    BelongsToQuote,
    HasFileFormat,
    Draftable,
    Handleable,
    HasColumnsData,
    HasScheduleData
};
use App\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteFile extends UuidModel implements HasOrderedScope
{
    use HasColumnsData, HasScheduleData, BelongsToQuote, BelongsToUser, HasFileFormat, Handleable, Draftable, SoftDeletes;

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

    public function scopePriceLists($query)
    {
        return $query->where('file_type', 'Distributor Price List');
    }

    public function scopePaymentSchedules($query)
    {
        return $query->where('file_type', 'Payment Schedule');
    }

    public function isExcel()
    {
        return $this->isFormat(['xls', 'xlsx']);
    }

    public function isWord()
    {
        return $this->isFormat(['doc', 'docx']);
    }

    public function isPdf()
    {
        return $this->isFormat('pdf');
    }

    public function isCsv()
    {
        return $this->isFormat('csv');
    }

    public function isNewSeparator($id)
    {
        if(!$this->propertyExists('dataSelectSeparator') || !isset($id)) {
            return false;
        }

        return $this->dataSelectSeparator->id !== $id;
    }

    public function isSchedule()
    {
        return $this->file_type === 'Payment Schedule';
    }

    private function isFormat($ext)
    {
        if(!$this->propertyExists('format')) {
            return false;
        }

        $extension = $this->format->extension;

        if(gettype($ext) === 'array') {
            return in_array($extension, $ext);
        }

        return $extension === $ext;
    }
}
