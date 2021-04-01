<?php

namespace App\Models\QuoteFile;

use App\Traits\{Auth\Multitenantable,
    BelongsToUser,
    Handleable,
    HasFileFormat,
    HasMetaAttributes,
    Import\Automappable,
    Misc\GeneratesException,
    Uuid};
use Illuminate\Database\Eloquent\{Builder,
    Collection,
    Model,
    Relations\BelongsTo,
    Relations\HasMany,
    Relations\HasOne,
    SoftDeletes};

/**
 * @property string|null $quote_file_format_id
 * @property string|null $file_type
 * @property string|null $original_file_path
 * @property string|null $original_file_name
 * @property string|null $replicated_quote_file_id
 * @property int|null $pages
 * @property int|null $imported_page
 *
 * @property ScheduleData|null scheduleData
 * @property Collection<MappedRow>|MappedRow[] $mappedRows
 * @property Collection<ImportedRow>|ImportedRow[] $rowsData
 */
class QuoteFile extends Model
{
    use Uuid,
        Multitenantable,
        Automappable,
        BelongsToUser,
        HasFileFormat,
        HasMetaAttributes,
        Handleable,
        GeneratesException,
        SoftDeletes;

    protected $fillable = [
        'original_file_path',
        'original_file_name',
        'file_type',
        'pages',
        'quote_file_format_id',
        'data_select_separator_id',
        'imported_page',
    ];

    public function scheduleData(): HasOne
    {
        return $this->hasOne(ScheduleData::class);
    }

    public function rowsData(): HasMany
    {
        return $this->hasMany(ImportedRow::class);
    }

    public function mappedRows(): HasMany
    {
        return $this->hasMany(MappedRow::class);
    }

    public function importedRawData(): HasMany
    {
        return $this->hasMany(ImportedRawData::class);
    }

    public function dataSelectSeparator(): BelongsTo
    {
        return $this->belongsTo(DataSelectSeparator::class)->withDefault();
    }

    public function scopePriceLists($query): Builder
    {
        return $query->where('file_type', QFT_PL);
    }

    public function scopePaymentSchedules($query): Builder
    {
        return $query->where('file_type', QFT_PS);
    }

    public function isNewSeparator($id): bool
    {
        if (!isset($this->dataSelectSeparator) || !isset($id)) {
            return false;
        }

        return $this->dataSelectSeparator->id !== $id;
    }

    public function isSchedule(): bool
    {
        return $this->file_type === QFT_PS;
    }

    public function isPrice(): bool
    {
        return $this->file_type === QFT_PL;
    }

    public function scopeIsNotHandledSchedule($query): Builder
    {
        return $query->where(fn($query) => $query->where('file_type', QFT_PS)->handled())->orWhere('file_type', QFT_PL);
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
        return $this->attributes['imported_page'] ?? $this->default_imported_page;
    }

    public function isNewPage($page): bool
    {
        if (!isset($page)) {
            return false;
        }

        return $this->imported_page !== (int)$page;
    }

    public function getItemNameAttribute()
    {
        return "Quote File ({$this->original_file_name})";
    }
}
