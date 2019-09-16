<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories \ {
    Quote\QuoteRepositoryInterface,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository
};
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
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    FindQuoteTemplateRequest
};
use Str;

class QuoteRepository implements QuoteRepositoryInterface
{
    protected $quote;

    protected $quoteFile;

    protected $quoteTemplate;

    protected $templateField;

    protected $importableColumn;

    protected $company;

    protected $dataSelectSeparator;

    public function __construct(
        Quote $quote,
        QuoteFile $quoteFile,
        QuoteTemplateRepository $quoteTemplate,
        TemplateField $templateField,
        ImportableColumn $importableColumn,
        Company $company,
        Vendor $vendor,
        DataSelectSeparator $dataSelectSeparator
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->quoteTemplate = $quoteTemplate;
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
        $quoteData = $state->get('quote_data');

        if($request->has('quote_id')) {
            $quote = $user->quotes()->find($request->quote_id)->firstOrNew([]);
        } else {
            $quote = $user->quotes()->make();
        }

        if(isset($quoteData)) {
            $quote->fill($quoteData);
            $quote->save();
        }

        if(data_get($state, 'quote_data.files')) {
            $stateFiles = collect(data_get($state, 'quote_data.files'));

            $stateFiles->each(function ($fileId) use ($quote) {
                $quoteFile = $this->quoteFile->whereId($fileId)->first();
                $quoteFile->quote()->associate($quote)->save();
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

        return $quote->load('quoteFiles', 'quoteTemplate');
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
            'quote_data.quote_template_id',
            'quote_data.customer_id',
            'quote_data.selected_rows',
            'save'
        ];

        return collect($request->only($stateModels));
    }

    public function findOrNew(string $id)
    {
        return $this->quote->whereId($id)->firstOrNew();
    }

    public function find(string $id)
    {
        return $this->quote->whereId($id)->first();
    }

    public function create(array $array)
    {
        return $this->quote->create($array);
    }

    public function make(array $array)
    {
        return $this->quote->make($array);
    }

    public function step1()
    {
        $companies = $this->company->with('vendors.countries')->get();
        $data_select_separators = $this->dataSelectSeparator->all();

        return compact('data_select_separators', 'companies');
    }

    public function getTemplates(GetQuoteTemplatesRequest $request)
    {
        return $this->quoteTemplate->findByCompanyVendorCountry(
            $request->company_id,
            $request->vendor_id,
            $request->country_id
        );
    }

    public function step2(FindQuoteTemplateRequest $request)
    {
        return $this->quoteTemplate->find($request->quote_template_id);
    }
}
