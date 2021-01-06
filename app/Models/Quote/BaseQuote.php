<?php

namespace App\Models\Quote;

use App\Casts\GroupDescription;
use App\Contracts\{
    ActivatableInterface,
    HasOrderedScope
};
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
    Activity\LogsActivity,
    Currency\ConvertsCurrency,
    Auth\Multitenantable,
    SavesPreviousState,
    Uuid
};
use Illuminate\Database\Eloquent\{
    SoftDeletes,
    Builder,
    Model
};
use Illuminate\Support\Traits\Tappable;
use Illuminate\Support\Str;

/**
 * @property \Illuminate\Support\Collection $group_description
 */
abstract class BaseQuote extends Model implements HasOrderedScope, ActivatableInterface
{
    use Uuid,
        Multitenantable,
        Searchable,
        // HasQuoteFiles,
        BelongsToUser,
        BelongsToCustomer,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountry,
        BelongsToMargin,
        BelongsToQuoteTemplate,
        BelongsToContractTemplate,
        // Draftable,
        // Submittable,
        // Activatable,
        SoftDeletes,
        HasMorphableDiscounts,
        HasMarginPercentageAttribute,
        HasPricesAttributes,
        HasMapping,
        HasCustomDiscountAttribute,
        HasGroupDescriptionAttribute,
        LogsActivity,
        HasAdditionalHtmlAttributes,
        Reviewable,
        Completable,
        SwitchesMode,
        ConvertsCurrency,
        SavesPreviousState,
        Tappable;

    const PRICE_ATTRIBUTES_MAPPING = [
        'pricing_document'      => 'pricing_document',
        'system_handle'         => 'system_handle',
        'service_agreement_id'  => 'searchable'
    ];

    const TYPES = ['New', 'Renewal'];

    protected $fillable = [
        'customer_id',
        'eq_customer_id',
        'distributor_file_id',
        'schedule_file_id',
        'company_id',
        'vendor_id',
        'country_id',
        'last_drafted_step',
        'completeness',
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
        'last_drafted_step'
    ];

    protected $casts = [
        'group_description'    => GroupDescription::class,
        // 'margin_data'          => 'array',
        'checkbox_status'      => 'json',
        'calculate_list_price' => 'boolean',
        'buy_price'            => 'float',
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $table = 'quotes';

    protected $dates = ['closing_date'];

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
        'exchange_rate_margin'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $saveStateAttributes = ['service_agreement_id'];

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeRfq($query, ?string $rfq)
    {
        return $query->whereHas('customer', fn (Builder $query) => $query->whereRfq($rfq));
    }

    public function scheduleData()
    {
        return $this->hasOneThrough(ScheduleData::class, QuoteFile::class, 'id', null, 'schedule_file_id')->withDefault();
    }

    public function priceList()
    {
        return $this->belongsTo(QuoteFile::class, 'distributor_file_id', 'id')->withDefault();
    }

    public function paymentSchedule()
    {
        return $this->belongsTo(QuoteFile::class, 'schedule_file_id', 'id')->withDefault();
    }

    public function rowsData()
    {
        return $this->hasManyThrough(ImportedRow::class, QuoteFile::class, 'id', null, 'distributor_file_id')
            ->whereColumn('imported_rows.page', '>=', 'quote_files.imported_page');
    }

    public function firstRow()
    {
        return $this->rowsData()->limit(1)->oldest();
    }

    public function toSearchArray()
    {
        return [
            'company_name'              => optional($this->company)->name,
            
            'customer_name'             => $this->customer->name,
            'customer_rfq'              => $this->customer->rfq,
            'customer_valid_until'      => $this->customer->quotation_valid_until,
            'customer_support_start'    => $this->customer->support_start_date,
            'customer_support_end'      => $this->customer->support_end_date,
            'customer_source'           => $this->customer->source,

            'user_fullname'             => optional($this->user)->fullname,
            'created_at'                => optional($this->created_at)->format(config('date.format'))
        ];
    }

    public function getCompletenessDictionary()
    {
        return __('quote.stages');
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

    public function getVersionNameAttribute(): string
    {
        $userName = !is_null($this->user_fullname) ? $this->user_fullname : "{$this->user->first_name} {$this->user->last_name}";

        if (blank($userName)) {
            $userName ??= "[USER DELETED]";
        }

        $versionNumber = $this->version_number ?? 1;

        return "{$userName} {$versionNumber}";
    }

    protected function defaultRelationships(): array
    {
        return [
            'quoteFiles' => fn ($query) => $query->isNotHandledSchedule(),
            'quoteTemplate',
            'countryMargin',
            'discounts',
            'customer',
            'country',
            'vendor'
        ];
    }
}
