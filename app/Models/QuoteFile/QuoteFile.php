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
    HasScheduleData,
    Import\Automappable
};
use App\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cache;

class QuoteFile extends UuidModel implements HasOrderedScope
{
    use Automappable, HasColumnsData, HasScheduleData, BelongsToQuote, BelongsToUser, HasFileFormat, Handleable, Draftable, SoftDeletes;

    protected $fillable = [
        'original_file_path', 'original_file_name', 'file_type', 'pages'
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
        return $this->belongsTo(DataSelectSeparator::class)->withDefault(DataSelectSeparator::make([]));
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
        return $this->file_type === __('quote_file.types.schedule');
    }

    public function scopeIsNotHandledSchedule($query)
    {
        return $query->where(function ($query) {
            $query->where('file_type', __('quote_file.types.schedule'))->handled();
        })->orWhere('file_type', __('quote_file.types.price'));
    }

    public function setImportedPage($page = null)
    {
        if(!isset($page)) {
            if($this->isExcel()) {
                $page = 1;
            } else {
                $page = 2;
            }
        }

        if($this->isCsv() || $this->isWord()) {
            $page = 1;
        }

        $this->setAttribute('imported_page', $page);

        return $this->save();
    }

    public function getRowsCountAttribute()
    {
        return (int) Cache::get("rows-count:{$this->id}", function () {
            return $this->rowsData()->count();
        });
    }

    public function setRowsCount(int $count)
    {
        return Cache::forever("rows-count:{$this->id}", $count);
    }

    public function getRowsProcessedCountAttribute()
    {
        return $this->rowsData()->processed()->count();
    }

    public function getProcessingStatusAttribute()
    {
        $percentage = $this->getAttribute('processing_percentage');

        return $percentage >= 100 ? 'completed' : 'processing';
    }

    public function setException(string $message)
    {
        return Cache::put("quote_file_exception:{$this->id}", $message);
    }

    public function getExceptionAttribute()
    {
        return Cache::get("quote_file_exception:{$this->id}", false);
    }

    public function clearException()
    {
        return Cache::forget("quote_file_exception:{$this->id}");
    }

    public function throwExceptionIfExists()
    {
        $exception = $this->getAttribute('exception');

        if($exception) {
            throw new \ErrorException($exception);
        }
    }

    public function getProcessingPercentageAttribute()
    {
        $rowsCount = $this->getAttribute('rows_count') ?: 1;
        $processedRowsCount = $this->getAttribute('rows_processed_count');

        if($processedRowsCount > $rowsCount) {
            $rowsCount = $processedRowsCount;
        }

        return floor($processedRowsCount / $rowsCount * 100);
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
