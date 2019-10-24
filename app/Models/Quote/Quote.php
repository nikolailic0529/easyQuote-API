<?php namespace App\Models\Quote;

use App\Contracts\HasOrderedScope;
use App\Models \ {
    CompletableModel,
    QuoteFile\ImportedRow,
    QuoteFile\QuoteFile
};
use App\Models\QuoteFile\ScheduleData;
use App\Traits \ {
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
    QuoteTemplate\BelongsToQuoteTemplate,
    Collaboration\BelongsToCollaboration
};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Setting;

class Quote extends CompletableModel implements HasOrderedScope
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
        BelongsToCollaboration,
        Draftable,
        Submittable,
        Activatable,
        SoftDeletes,
        HasMorphableDiscounts,
        HasMarginPercentageAttribute,
        HasPricesAttributes,
        HasMapping;

    public $computableRows = null;

    public $list_price = null;

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
        'additional_notes',
        'calculate_list_price',
        'buy_price'
    ];

    protected $attributes = [
        'completeness' => 1,
        'calculate_list_price' => false
    ];

    protected $appends = [
        'last_drafted_step',
        'closing_date',
        'margin_percentage',
        'margin_percentage_without_country_margin',
        'margin_percentage_without_discounts'
    ];

    protected $casts = [
        'margin_data' => 'array',
        'checkbox_status' => 'json',
        'calculate_list_price' => 'boolean',
        'buy_price' => 'decimal,2'
    ];

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
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeWithJoins($query)
    {
        return $query->with($this->joins());
    }

    public function loadJoins()
    {
        return $this->load($this->joins());
    }

    public function scheduleData()
    {
        return $this->hasOneThrough(ScheduleData::class, QuoteFile::class)->withDefault(ScheduleData::make([]));
    }

    public function selectedRowsData()
    {
        return $this->rowsData()->selected();
    }

    public function appendJoins()
    {
        return $this->setAppends(
            [
                'last_drafted_step',
                'field_column',
                'rows_data',
                'margin_percentage',
                'margin_percentage_without_country_margin',
                'margin_percentage_without_discounts',
                'user_margin_percentage',
                'list_price'
            ]
        );
    }

    public function rowsData()
    {
        $importedPage = $this->quoteFiles()->priceLists()->first()->imported_page ?? Setting::get('parser.default_page');

        return $this->hasManyThrough(ImportedRow::class, QuoteFile::class)
            ->where('quote_files.file_type', __('quote_file.types.price'))
            ->where('imported_rows.page', '>=', $importedPage);
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
        $this->load('customer', 'company');

        return collect($this->toArray())->except(['margin_data', 'checkbox_status', 'calculate_list_price'])->toArray();
    }

    public function getCompletenessDictionary()
    {
        return __('quote.stages');
    }

    public function getClosingDateAttribute()
    {
        if(!isset($this->attributes['closing_date'])) {
            return null;
        }

        return Carbon::parse($this->attributes['closing_date'])->format('d/m/Y');
    }

    private function joins() {
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
