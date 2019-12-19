<?php

namespace App\Models\QuoteFile;

use App\Models\{
    UuidModel,
    QuoteFile\ImportedRawData,
    QuoteFile\ImportedRow,
    QuoteFile\DataSelectSeparator
};
use App\Traits\{
    BelongsToUser,
    BelongsToQuote,
    HasFileFormat,
    Draftable,
    Handleable,
    HasScheduleData,
    Import\Automappable
};
use App\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteFile extends UuidModel implements HasOrderedScope
{
    use Automappable,
        HasScheduleData,
        BelongsToQuote,
        BelongsToUser,
        HasFileFormat,
        Handleable,
        Draftable,
        SoftDeletes;

    protected $fillable = [
        'original_file_path',
        'original_file_name',
        'file_type',
        'pages',
        'quote_file_format_id',
        'quote_id',
        'imported_page'
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
        return $this->hasManyThrough(ImportedColumn::class, ImportedRow::class);
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

    public function scopeGeneratedPdf($query)
    {
        return $query->where('file_type', 'Generated PDF');
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
        if (!$this->propertyExists('dataSelectSeparator') || !isset($id)) {
            return false;
        }

        return $this->dataSelectSeparator->id !== $id;
    }

    public function isSchedule()
    {
        return $this->file_type === __('quote_file.types.schedule');
    }

    public function isPrice()
    {
        return $this->file_type === __('quote_file.types.price');
    }

    public function scopeIsNotHandledSchedule($query)
    {
        return $query->where(function ($query) {
            $query->where('file_type', __('quote_file.types.schedule'))->handled();
        })->orWhere('file_type', __('quote_file.types.price'));
    }

    public function setImportedPage(?int $imported_page)
    {
        if (blank($imported_page)) {
            $imported_page = $this->default_imported_page;
        }

        $this->attributes['imported_page'] = min($imported_page, $this->pages);

        return $this->save();
    }

    public function getDefaultImportedPageAttribute()
    {
        if ($this->isPrice()) {
            return min((($this->isExcel() || $this->isCsv() || $this->isWord()) ? 1 : 2), $this->pages);
        }

        return $this->pages;
    }

    public function getImportedPageAttribute()
    {
        return $this->attributes['imported_page']
            ?? $this->default_imported_page;
    }

    public function getRowsCountAttribute()
    {
        return (int) cache()->sear("rows-count:{$this->id}", function () {
            return $this->rowsData()->count();
        });
    }

    public function setRowsCount(int $count)
    {
        return cache()->forever("rows-count:{$this->id}", $count);
    }

    public function getRowsProcessedCountAttribute()
    {
        return $this->rowsData()->processed()->count();
    }

    public function getProcessingStatusAttribute()
    {
        if ($this->isSchedule()) {
            return 'completed';
        }

        $percentage = $this->getAttribute('processing_percentage');

        return $percentage >= 100 ? 'completed' : 'processing';
    }

    public function getProcessingStateAttribute()
    {
        return [
            'status' => $this->processing_status,
            'processed' => $this->processing_percentage
        ];
    }

    public function setException(string $code): bool
    {
        return cache()->forever("quote_file_exception:{$this->id}", $code);
    }

    public function getExceptionAttribute()
    {
        return cache("quote_file_exception:{$this->id}", false);
    }

    public function clearException()
    {
        return cache()->forget("quote_file_exception:{$this->id}");
    }

    public function throwExceptionIfExists()
    {
        error_abort_if($this->exception, $this->exception, 422);
    }

    public function getProcessingPercentageAttribute()
    {
        if ($this->isSchedule()) {
            return 100;
        }

        $rowsCount = $this->getAttribute('rows_count') ?: 1;
        $processedRowsCount = $this->getAttribute('rows_processed_count');

        if ($processedRowsCount > $rowsCount) {
            $rowsCount = $processedRowsCount;
        }

        return floor($processedRowsCount / $rowsCount * 100);
    }

    public function isNewPage($page)
    {
        if (!isset($page)) {
            return false;
        }

        return $this->attributes['imported_page'] !== ((int) $page);
    }

    public function getItemNameAttribute()
    {
        return "Quote File ({$this->original_file_name})";
    }

    private function isFormat($ext)
    {
        if (!$this->propertyExists('format')) {
            return false;
        }

        $extension = $this->format->extension;

        if (gettype($ext) === 'array') {
            return in_array($extension, $ext);
        }

        return $extension === $ext;
    }
}
