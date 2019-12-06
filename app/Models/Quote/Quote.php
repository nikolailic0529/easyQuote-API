<?php

namespace App\Models\Quote;

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
    Search\Searchable,
    Discount\HasMorphableDiscounts,
    Margin\HasMarginPercentageAttribute,
    Quote\HasPricesAttributes,
    Quote\HasMapping,
    Quote\HasCustomDiscountAttribute,
    Quote\HasGroupDescriptionAttribute,
    Quote\HasSubmittedDataAttribute,
    QuoteTemplate\BelongsToQuoteTemplate
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;
use Arr;

class Quote extends CompletableModel implements HasOrderedScope, ActivatableInterface
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
        LogsActivity;

    public $computableRows = null;

    public $applicable_discounts = 0.0;

    public $applicable_custom_discount = 0.0;

    protected $perPage = 8;

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
        'additional_details',
        'checkbox_status',
        'closing_date',
        'additional_notes'
    ];

    protected $attributes = [
        'completeness' => 1,
        'calculate_list_price' => false
    ];

    protected $appends = [
        'last_drafted_step',
        'closing_date',
        'hidden_fields',
        'sort_fields',
        'field_column',
        'rows_data'
    ];

    protected $casts = [
        'margin_data' => 'array',
        'checkbox_status' => 'json',
        'calculate_list_price' => 'boolean',
        'buy_price' => 'float'
    ];

    protected $hidden = [
        'deleted_at'
    ];

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
        'additional_details',
        'closing_date',
        'additional_notes',
        'calculate_list_price',
        'custom_discount',
        'use_groups',
        'buy_price',
        'submitted_at'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

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

        return Arr::except(
            $this->toArray(),
            [
                'margin_data',
                'checkbox_status',
                'calculate_list_price',
                'vendor.logo',
                'company.logo',
                'discounts'
            ]
        );
    }

    public function getCompletenessDictionary()
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
}
