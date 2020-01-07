<?php

namespace App\Models\Quote;

use App\Models\BaseModel;
use App\Contracts\{
    ActivatableInterface,
    HasOrderedScope
};
use App\Models\{
    CompletableModel,
    QuoteFile\ImportedRow,
    QuoteFile\QuoteFile,
    QuoteFile\ScheduleData
};
use App\Traits\{
    Activatable,
    HasQuoteFiles,
    BelongsToUser,
    BelongsToCustomer,
    BelongsToCompany,
    BelongsToVendor,
    BelongsToCountry,
    BelongsToMargin,
    Draftable,
    Submittable,
    Reviewable,
    Completable,
    Search\Searchable,
    Discount\HasMorphableDiscounts,
    Margin\HasMarginPercentageAttribute,
    Quote\HasPricesAttributes,
    Quote\HasMapping,
    Quote\HasCustomDiscountAttribute,
    Quote\HasGroupDescriptionAttribute,
    Quote\HasSubmittedDataAttribute,
    Quote\HasAdditionalHtmlAttributes,
    QuoteTemplate\BelongsToQuoteTemplate,
    CachesRelations\CachesRelations,
    Activity\LogsActivity,
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Arr, Str;

abstract class BaseQuote extends BaseModel implements HasOrderedScope, ActivatableInterface
{
    use Searchable,
        HasQuoteFiles,
        BelongsToUser,
        BelongsToCustomer,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountry,
        BelongsToMargin,
        BelongsToQuoteTemplate,
        Draftable,
        Submittable,
        Activatable,
        SoftDeletes,
        HasMorphableDiscounts,
        HasMarginPercentageAttribute,
        HasPricesAttributes,
        HasMapping,
        HasCustomDiscountAttribute,
        HasGroupDescriptionAttribute,
        HasSubmittedDataAttribute,
        LogsActivity,
        CachesRelations,
        HasAdditionalHtmlAttributes,
        Reviewable,
        Completable;

    protected $connection = 'mysql';

    protected $fillable = [
        'type',
        'customer_id',
        'company_id',
        'vendor_id',
        'country_id',
        'language_id',
        'quote_template_id',
        'last_drafted_step',
        'pricing_document',
        'service_agreement_id',
        'system_handle',
        'checkbox_status',
        'closing_date'
    ];

    protected $attributes = [
        'completeness' => 1,
        'calculate_list_price' => false
    ];

    protected $appends = [
        'last_drafted_step',
        'closing_date'
    ];

    protected $casts = [
        'margin_data' => 'array',
        'checkbox_status' => 'json',
        'calculate_list_price' => 'boolean',
        'buy_price' => 'float'
    ];

    protected $hidden = [
        'deleted_at', 'cached_relations'
    ];

    protected $table = 'quotes';

    protected static $logAttributes = [
        'type',
        'customer.name',
        'company.name',
        'vendor.name',
        'country.name',
        'countryMargin.formatted_value',
        'quoteTemplate.name',
        'last_drafted_step',
        'pricing_document',
        'service_agreement_id',
        'system_handle',
        'closing_date',
        'calculate_list_price',
        'custom_discount',
        'use_groups',
        'buy_price',
        'submitted_at'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $cacheRelations = ['user', 'customer', 'company'];

    public function scopeNewType($query)
    {
        return $query->whereType('New');
    }

    public function scopeRenewalType($query)
    {
        return $query->whereType('Renewal');
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeRfq($query, string $rfq)
    {
        return $query->whereHas('customer', function ($query) use ($rfq) {
            $query->whereRfq($rfq);
        });
    }

    public function scheduleData()
    {
        return $this->hasOneThrough(ScheduleData::class, QuoteFile::class)->withDefault(ScheduleData::make([]));
    }

    public function selectedRowsData()
    {
        return $this->rowsData()->selected();
    }

    public function priceList()
    {
        return $this->hasOne(QuoteFile::class)->priceLists()->withDefault(QuoteFile::make([]));
    }

    public function paymentSchedule()
    {
        return $this->hasOne(QuoteFile::class)->paymentSchedules()->withDefault(QuoteFile::make([]));
    }

    public function generatedPdf()
    {
        return $this->hasOne(QuoteFile::class)->generatedPdf()->withDefault(QuoteFile::make([]));
    }

    public function rowsData()
    {
        return $this->hasManyThrough(ImportedRow::class, QuoteFile::class)
            ->where('quote_files.file_type', __('quote_file.types.price'))
            ->whereColumn('imported_rows.page', '>=', 'quote_files.imported_page');
    }

    public function getRowsDataAttribute()
    {
        return $this->rowsData()->with('columnsData')->processed()->limit(1)->get();
    }

    public function detachQuoteFile(QuoteFile $quoteFile)
    {
        return $this->quoteFiles()->detach($quoteFile->id);
    }

    public function toSearchArray()
    {
        $this->load('customer', 'company', 'vendor');

        return Arr::only($this->toArray(), ['customer', 'company', 'vendor', 'user']);
    }

    public static function getCompletenessDictionary()
    {
        return __('quote.stages');
    }

    public function getClosingDateAttribute()
    {
        if (!isset($this->attributes['closing_date'])) {
            return null;
        }

        return Carbon::parse($this->attributes['closing_date'])->format('d/m/Y');
    }

    public function getItemNameAttribute()
    {
        $customer_rfq = $this->customer->rfq ?? 'unknown RFQ';

        return "Quote ({$customer_rfq})";
    }

    public function withAppends(...$attributes)
    {
        isset($this->quoteTemplate) && $this->quoteTemplate->makeVisible(['form_data', 'form_values_data']);

        $attributes = array_merge($attributes, [
            'list_price',
            'hidden_fields',
            'sort_fields',
            'field_column',
            'rows_data',
            'margin_percentage_without_country_margin',
            'margin_percentage_without_discounts',
            'user_margin_percentage'
        ]);

        return $this->append($attributes);
    }

    public function scopeWithDefaultRelations(Builder $query): Builder
    {
        return $query->with($this->defaultRelationships());
    }

    public function loadDefaultRelations(): self
    {
        return $this->loadMissing($this->defaultRelationships());
    }

    public function getForeignKey()
    {
        return Str::snake(Str::after(class_basename(self::class), 'Base')).'_'.$this->getKeyName();
    }

    protected function defaultRelationships(): array
    {
        return [
            'quoteFiles' => function ($query) {
                return $query->isNotHandledSchedule();
            },
            'quoteTemplate.templateFields.templateFieldType',
            'countryMargin',
            'discounts',
            'customer',
            'country',
            'vendor'
        ];
    }
}
