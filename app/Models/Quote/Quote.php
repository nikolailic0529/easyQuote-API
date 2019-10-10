<?php namespace App\Models\Quote;

use App\Contracts\HasOrderedScope;
use App\Models \ {
    CompletableModel,
    Quote\FieldColumn,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    QuoteFile\ImportableColumn,
    QuoteFile\ImportedRow,
    QuoteFile\QuoteFile
};
use App\Models\QuoteFile\ScheduleData;
use App\Traits \ {
    Activatable,
    Search\Searchable,
    HasQuoteFiles,
    BelongsToUser,
    BelongsToCustomer,
    BelongsToCompany,
    BelongsToVendor,
    BelongsToCountry,
    BelongsToMargin,
    Draftable
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
        Draftable,
        Activatable,
        SoftDeletes;

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

    protected $perPage = 8;

    protected $attributes = [
        'completeness' => 1,
        'calculate_list_price' => false
    ];

    protected $appends = [
        'last_drafted_step',
        'closing_date',
        'margin_percentage'
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

    public function templateFields()
    {
        return $this->belongsToMany(TemplateField::class, 'quote_field_column', 'quote_id');
    }

    public function importableColumns()
    {
        return $this->belongsToMany(ImportableColumn::class, 'quote_field_column', 'quote_id');
    }

    public function quoteTemplate()
    {
        return $this->belongsTo(QuoteTemplate::class);
    }

    public function scheduleData()
    {
        return $this->hasOneThrough(ScheduleData::class, QuoteFile::class)->withDefault(ScheduleData::make([]));
    }

    public function appendJoins()
    {
        return $this->setAppends(['last_drafted_step', 'field_column', 'rows_data', 'margin_percentage']);
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'quote_discount')
            ->withPivot('duration')
            ->with('discountable')->whereHasMorph('discountable', [
                \App\Models\Quote\Discount\MultiYearDiscount::class,
                \App\Models\Quote\Discount\PrePayDiscount::class,
                \App\Models\Quote\Discount\PromotionalDiscount::class,
                \App\Models\Quote\Discount\SND::class
            ]);
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

    public function getRowsDataByColumnsAttribute($selected = false)
    {
        $fieldsColumns = $this->fieldsColumns()->with('importableColumn', 'templateField')->get();
        $importableColumns = data_get($fieldsColumns, '*.importable_column_id');

        $query = $selected ? $this->selectedRowsData() : $this->rowsData();

        return $query->with(['columnsData' => function ($query) use ($importableColumns) {
            $query->whereIn('imported_columns.importable_column_id', $importableColumns)
            ->join('quote_field_column', function ($join) {
                $join->on('imported_columns.importable_column_id', '=', 'quote_field_column.importable_column_id')
                    ->where('quote_field_column.quote_id', '=', $this->id);
            })
            ->join('template_fields', function ($join) {
                $join->on('template_fields.id', '=', 'quote_field_column.template_field_id')
                    ->select('template_fields.name as template_field_name');
            })->select(['imported_columns.*', 'template_fields.name as template_field_name']);
        }])->get();
    }

    public function getSelectedRowsDataByColumnsAttribute()
    {
        return $this->getRowsDataByColumnsAttribute(true);
    }

    public function defaultTemplateFields()
    {
        return $this->templateFields()->with('systemImportableColumn')->where('is_default_enabled', true);
    }

    public function fieldsColumns()
    {
        return $this->hasMany(FieldColumn::class);
    }

    public function selectedRowsData()
    {
        return $this->rowsData()->selected();
    }

    public function getFieldColumnAttribute()
    {
        if(!isset($this->quoteTemplate) || !isset($this->quoteTemplate->templateFields)) {
            return [];
        }

        $quoteTemplate = $this->quoteTemplate()->with(['templateFields.fieldColumn' => function ($query) {
            return $query->where('quote_id', $this->id)->withDefault(FieldColumn::make([]));
        }])->first();

        $templateFields = $quoteTemplate->templateFields->map(function ($templateField) {
            $template_field_id = $templateField->id;
            return array_merge(compact('template_field_id'), $templateField->fieldColumn->toArray());
        });

        return $templateFields;
    }

    public function attachColumnToField(TemplateField $templateField, $importableColumn, array $attributes = [])
    {
        $template_field_id = $templateField->id;
        $importable_column_id = $importableColumn->id ?? null;
        $attributes = array_intersect_key($attributes, (new FieldColumn)->getAttributes());
        $attributes = array_merge($attributes, compact('importable_column_id'));

        if($this->templateFields()->whereId($template_field_id)->exists()) {
            return $this->templateFields()->updateExistingPivot(
                $template_field_id, $attributes
            );
        }

        return $this->templateFields()->attach([
            $template_field_id => $attributes
        ]);
    }

    public function detachTemplateField(TemplateField $templateField)
    {
        return $this->templateFields()->detach($templateField->id);
    }

    public function detachColumnsFields()
    {
        return $this->templateFields()->detach();
    }

    public function detachQuoteFile(QuoteFile $quoteFile)
    {
        return $this->quoteFiles()->detach($quoteFile->id);
    }

    public function createCountryMargin(array $attributes)
    {
        if(!isset($this->user) || !isset($this->vendor) || !isset($this->country)) {
            return null;
        }

        $user = request()->user();

        $this->countryMargin()->dissociate();

        $countryMargin = $user->countryMargins()->quoteAcceptable($this)->firstOrNew(collect($attributes)->except('type')->toArray());

        if($countryMargin->isDirty()) {
            $countryMargin->user()->associate($this->user);
            $countryMargin->country()->associate($this->country);
            $countryMargin->vendor()->associate($this->vendor);
            $countryMargin->save();
        }

        $this->countryMargin()->associate($countryMargin);

        $this->margin_data = collect($countryMargin->only('value', 'method', 'is_fixed'))->put('type', 'By Country')->toArray();

        $this->setAttribute('type', $attributes['quote_type']);
        $this->save();

        return $countryMargin;
    }

    public function deleteCountryMargin()
    {
        $this->countryMargin()->delete();
        $this->countryMargin()->dissociate();

        return $this;
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

    public function getMappingAttribute()
    {
        $mapping = $this->fieldsColumns()
            ->with('importableColumn', 'templateField.systemImportableColumn')
            ->get()
            ->mapWithKeys(function ($fieldColumn) {
                return [$fieldColumn->templateField->name => $fieldColumn->importableColumn->id ?? $fieldColumn->templateField->systemImportableColumn->id];
            });

        return $mapping;
    }

    public function getApplicableDiscountsFormattedAttribute()
    {
        return number_format($this->attributes['applicable_discounts'] ?? 0, 2);
    }

    public function getApplicableDiscountsAttribute()
    {
        return (float) ($this->attributes['applicable_discounts'] ?? 0);
    }

    public function getListPriceAttribute()
    {
        return (float) ($this->attributes['list_price'] ?? 0);
    }

    public function getListPriceFormattedAttribute()
    {
        return number_format($this->getAttribute('list_price'), 2);
    }

    public function getMarginPercentageAttribute()
    {
        if($this->list_price === 0.00 || $this->buy_price > $this->list_price) {
            return 0;
        }

        return (($this->list_price - $this->buy_price) / $this->list_price) * 100;
    }

    public function getFinalPriceAttribute()
    {
        $final_price = ((float) $this->getAttribute('list_price')) - ((float) $this->getAttribute('applicable_discounts'));

        return number_format($final_price, 2);
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
