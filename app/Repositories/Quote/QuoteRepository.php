<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteRepositoryInterface;
use App\Models \ {
    Quote\Quote,
    Company,
    Vendor,
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteFile\DataSelectSeparator,
    QuoteFile\ImportedRow,
    QuoteTemplate\TemplateField
};
use App\Http\Requests\StoreQuoteStateRequest;
use Str;

class QuoteRepository implements QuoteRepositoryInterface
{
    protected $quote;

    protected $quoteFile;

    protected $templateField;

    protected $importableColumn;
    
    protected $company;
    
    protected $dataSelectSeparator;

    public function __construct(
        Quote $quote,
        QuoteFile $quoteFile,
        TemplateField $templateField,
        ImportableColumn $importableColumn,
        Company $company,
        Vendor $vendor,
        DataSelectSeparator $dataSelectSeparator
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->templateField = $templateField;
        $this->importableColumn = $importableColumn;
        $this->company = $company;
        $this->vendor = $vendor;
        $this->dataSelectSeparator = $dataSelectSeparator;
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        $user = $request->user();
        $state = $this->filterState($request);

        if($request->has('quote_id')) {
            $quote = $user->quotes()->find($request->quote_id)->firstOrNew([]);
            $quote->fill($state->toArray());
        } else {
            $quote = $user->quotes()->make($state->toArray());
        }

        if(data_get($state, 'quote_data.files')) {
            $stateFiles = collect(data_get($state, 'quote_data.files'));
            
            $stateFiles->each(function ($fileId, $key) use ($quote) {
                $quoteFile = $this->quoteFile->whereId($fileId)->first();
                $quoteFile->quote()->associate($quote);
            });
        }

        if(data_get($state, 'quote_data.field_column')) {
            collect(data_get($state, 'quote_data.field_column'))->each(function ($relation) use ($quote) {
                ['template_field_id' => $templateFieldId, 'importable_column_id' => $importableColumnId] = $relation;

                $templateField = $this->templateField->whereId($templateFieldId)->first();
                $importableColumn = $this->importableColumn->whereId($importableColumnId)->first();

                $quote->attachColumnToField($templateField, $importableColumn);
            });
        }

        /**
         * Saving current state
         */
        if($state->has('save') && $state->get('save')) {
            $quote->markAsNotDrafted();
        } else {
            $quote->markAsDrafted();
        }

        return $quote->load('quoteFiles', 'templateFields');
    }

    protected function filterState(StoreQuoteStateRequest $request)
    {
        $stateModels = [
            'quote_data.company_id',
            'quote_data.vendor_id',
            'quote_data.country_id',
            'quote_data.language_id',
            'quote_data.files',
            'quote_data.field_column',
            'save'
        ];

        return collect($request->only($stateModels));
    }

    public function findOrNew(String $id)
    {
        return $this->quote->whereId($id)->firstOrNew();
    }

    public function find(String $id)
    {
        return $this->quote->whereId($id)->first();
    }

    public function create(Array $array)
    {
        return $this->quote->create($array);
    }

    public function make(Array $array)
    {
        return $this->quote->make($array);
    }

    public function step1()
    {
        $companies = $this->company->with('vendors.countries.languages')->get();
        $data_select_separators = $this->dataSelectSeparator->all();

        return compact('data_select_separators', 'companies');
    }

    public function step2()
    {
        $templateFields = $this->templateField
            ->with('templateFieldType')->ordered()->get();
        
        $templateFields = $templateFields->map(function ($field) {
            $fieldName = $field->templateFieldType->name;
            $field->makeHidden('templateFieldType');
            $field->field_type = $fieldName;

            return $field;
        });

        return $templateFields;
    }
}