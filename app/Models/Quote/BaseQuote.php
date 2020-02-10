<?php

namespace App\Models\Quote;

use App\Models\BaseModel;
use App\Contracts\{
    ActivatableInterface,
    HasOrderedScope
};
use App\Http\Resources\ImportedRow\ImportedRowResource;
use App\Models\{
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
    Quote\SwitchesMode,
    Quote\HasCustomDiscountAttribute,
    Quote\HasGroupDescriptionAttribute,
    Quote\HasAdditionalHtmlAttributes,
    QuoteTemplate\BelongsToQuoteTemplate,
    QuoteTemplate\BelongsToContractTemplate,
    CachesRelations\CachesRelations,
    Activity\LogsActivity,
    Currency\ConvertsCurrency,
    Auth\Multitenantable
};
use Illuminate\Database\Eloquent\{
    SoftDeletes,
    Builder
};
use Illuminate\Support\Traits\Tappable;
use Carbon\Carbon;
use Str;

abstract class BaseQuote extends BaseModel implements HasOrderedScope, ActivatableInterface
{
    use Multitenantable,
        Searchable,
        HasQuoteFiles,
        BelongsToUser,
        BelongsToCustomer,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountry,
        BelongsToMargin,
        BelongsToQuoteTemplate,
        BelongsToContractTemplate,
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
        LogsActivity,
        CachesRelations,
        HasAdditionalHtmlAttributes,
        Reviewable,
        Completable,
        SwitchesMode,
        ConvertsCurrency,
        Tappable;

    protected $connection = 'mysql';

    protected $fillable = [
        'type',
        'customer_id',
        'company_id',
        'vendor_id',
        'country_id',
        'last_drafted_step',
        'completeness',
        'language_id',
        'quote_template_id',
        'contract_template_id',
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
        'sourceCurrency.code',
        'targetCurrency.code',
        'exchange_rate_margin',
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
        return $this->hasOneThrough(ScheduleData::class, QuoteFile::class)->withDefault();
    }

    public function selectedRowsData()
    {
        return $this->rowsData()->selected();
    }

    public function priceList()
    {
        return $this->hasOne(QuoteFile::class)->priceLists()->withDefault();
    }

    public function paymentSchedule()
    {
        return $this->hasOne(QuoteFile::class)->paymentSchedules()->withDefault();
    }

    public function rowsData()
    {
        return $this->hasManyThrough(ImportedRow::class, QuoteFile::class)
            ->where('quote_files.file_type', __('quote_file.types.price'))
            ->whereColumn('imported_rows.page', '>=', 'quote_files.imported_page');
    }

    public function getRowsDataAttribute()
    {
        return ImportedRowResource::collection(
            $this->rowsData()->with('columnsData')->processed()->oldest()->limit(1)->get()
        );
    }

    public function detachQuoteFile(QuoteFile $quoteFile)
    {
        return $this->quoteFiles()->detach($quoteFile->id);
    }

    public function toSearchArray()
    {
        return [
            'company_name'              => optional($this->company)->name,
            'customer_name'             => optional($this->customer)->name,
            'customer_rfq'              => optional($this->customer)->rfq,
            'customer_valid_until'      => optional($this->customer)->valid_until,
            'customer_support_start'    => optional($this->customer)->support_start,
            'customer_support_end'      => optional($this->customer)->support_end,
            'user_fullname'             => optional($this->user)->fullname,
            'created_at'                => $this->created_at
        ];
    }

    public static function getCompletenessDictionary()
    {
        return __('quote.stages');
    }

    public function getClosingDateAttribute($value)
    {
        return carbon_format($value, config('date.format_ui'));
    }

    public function getItemNameAttribute()
    {
        return "Quote ({$this->customer->rfq})";
    }

    public function getCurrencySymbolAttribute()
    {
        return $this->targetCurrency->symbol
            ?? $this->sourceCurrency->symbol
            ?? $this->quoteTemplate->currency->symbol
            ?? null;
    }

    public function withAppends(...$attributes)
    {
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
        return Str::snake(Str::after(class_basename(self::class), 'Base')) . '_' . $this->getKeyName();
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
