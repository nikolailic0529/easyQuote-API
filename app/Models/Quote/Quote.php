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
use App\Traits \ {
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
use Setting;

class Quote extends CompletableModel implements HasOrderedScope
{
    use Searchable, HasQuoteFiles, BelongsToUser, BelongsToCustomer, BelongsToCompany,
    BelongsToVendor, BelongsToCountry, BelongsToMargin, Draftable;

    protected $fillable = ['type', 'customer_id', 'company_id', 'vendor_id', 'country_id', 'language_id', 'quote_template_id', 'last_drafted_step'];

    protected $perPage = 8;

    protected $attributes = [
        'completeness' => 1
    ];

    protected $appends = [
        'last_drafted_step'
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

    public function appendJoins()
    {
        return $this->setAppends(['last_drafted_step', 'field_column', 'rows_data', 'rows_data_by_columns']);
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
        return $this->rowsData()->with('columnsData')->get();
    }

    public function getRowsDataByColumnsAttribute()
    {
        $fieldsColumns = $this->fieldsColumns()->with('importableColumn', 'templateField')->get();
        $importableColumns = data_get($fieldsColumns, '*.importable_column_id');

        return $this->rowsData()->with(['columnsData' => function ($query) use ($importableColumns) {
            return $query->whereHas('importableColumn', function ($query) use ($importableColumns) {
                return $query->whereIn('id', $importableColumns)
                    ->ordered();
            })->join('quote_field_column', function ($join) {
                return $join->on('imported_columns.importable_column_id', '=', 'quote_field_column.importable_column_id')
                    ->where('quote_field_column.quote_id', '=', $this->id);
            })
            ->join('template_fields', function ($join) {
                return $join->on('template_fields.id', '=', 'quote_field_column.template_field_id')
                    ->select('template_fields.name as template_field_name');
            })->select(['imported_columns.*', 'template_fields.name as template_field_name']);
        }])->get();
    }

    public function defaultTemplateFields()
    {
        return $this->templateFields()->with('systemImportableColumn')->where('importable_column_id', null)->where('is_default_enabled', true);
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

        $this->countryMargin()->dissociate();

        $countryMargin = $this->countryMargin()->make($attributes);
        $countryMargin->user()->associate($this->user);
        $countryMargin->country()->associate($this->country);
        $countryMargin->vendor()->associate($this->vendor);
        $countryMargin->save();

        $this->countryMargin()->associate($countryMargin);

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
        $this->load('customer');

        return $this->toArray();
    }

    public function getCompletenessDictionary()
    {
        return __('quote.stages');
    }

    private function joins() {
        return [
            'quoteFiles' => function ($query) {
                return $query->isNotHandledSchedule();
            },
            'selectedRowsData.columnsData',
            'quoteTemplate.templateFields.templateFieldType',
            'countryMargin',
            'customer'
        ];
    }
}
