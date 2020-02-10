<?php

namespace App\Models\QuoteFile;

use App\Models\{
    BaseModel,
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
    HasMetaAttributes,
    HasScheduleData,
    Import\Automappable,
    Misc\GeneratesException
};
use App\Contracts\HasOrderedScope;
use App\Traits\Auth\Multitenantable;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteFile extends BaseModel implements HasOrderedScope
{
    use Multitenantable,
        Automappable,
        HasScheduleData,
        BelongsToQuote,
        BelongsToUser,
        HasFileFormat,
        HasMetaAttributes,
        Handleable,
        Draftable,
        GeneratesException,
        SoftDeletes;

    protected $fillable = [
        'original_file_path',
        'original_file_name',
        'file_type',
        'pages',
        'quote_file_format_id',
        'data_select_separator_id',
        'quote_id',
        'imported_page'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('created_at');
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
        return $query->where('file_type', QFT_PL);
    }

    public function scopePaymentSchedules($query)
    {
        return $query->where('file_type', QFT_PS);
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

    public function isNewPage($page)
    {
        if (!isset($page)) {
            return false;
        }

        return $this->imported_page !== (int) $page;
    }

    public function getItemNameAttribute()
    {
        return "Quote File ({$this->original_file_name})";
    }
}
