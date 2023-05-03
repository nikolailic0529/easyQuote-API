<?php

namespace App\Domain\QuoteFile\Models;

use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\QuoteFile\Concerns\Handleable;
use App\Domain\QuoteFile\Concerns\HasFileFormat;
use App\Domain\QuoteFile\Concerns\HasMetaAttributes;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @property string|null $quote_file_format_id
 * @property string|null $file_type
 * @property string|null $original_file_path
 * @property string|null $original_file_name
 * @property string|null $replicated_quote_file_id
 * @property int|null    $pages
 * @property int|null    $imported_page
 * @property string|null $handled_at
 * @property array|null  $meta_attributes
 * @property ScheduleData|null scheduleData
 * @property Collection<MappedRow>|MappedRow[]     $mappedRows
 * @property Collection<ImportedRow>|ImportedRow[] $rowsData
 * @property QuoteFileFormat|null                  $format
 */
class QuoteFile extends Model
{
    use Uuid;
    use Multitenantable;
    use BelongsToUser;
    use HasFileFormat;
    use HasMetaAttributes;
    use Handleable;
    use SoftDeletes;

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
        return $query->where(fn ($query) => $query->where('file_type', QFT_PS)->handled())->orWhere('file_type', QFT_PL);
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
            return min(($this->isExcel() || $this->isCsv() || $this->isWord()) ? 1 : 2, $this->pages);
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

        return $this->imported_page !== (int) $page;
    }

    public function getItemNameAttribute()
    {
        return "Quote File ({$this->original_file_name})";
    }

    public function mappingWasGuessed(): bool
    {
        return !is_null($this->automapped_at);
    }

    public function worldwideDistributorQuotesWhereQuoteFileAsPriceList(): HasMany
    {
        return $this->hasMany(related: WorldwideDistribution::class, foreignKey: 'distributor_file_id');
    }

    public function setException(string $message, string $code): bool
    {
        return cache()->forever($this->getExceptionCacheKey(), compact('message', 'code'));
    }

    public function getExceptionAttribute()
    {
        return cache($this->getExceptionCacheKey(), false);
    }

    public function hasException(): bool
    {
        return (bool) $this->exception;
    }

    public function clearException()
    {
        return cache()->forget($this->getExceptionCacheKey());
    }

    public function throwExceptionIfExists()
    {
        if ($this->exception && Arr::has($this->exception, ['message', 'code'])) {
            error_abort($this->exception['message'], $this->exception['code'], 422);
        }
    }

    protected function getExceptionCacheKey(): string
    {
        return Str::snake(class_basename($this)).'_exception:'.$this->getKey();
    }
}
