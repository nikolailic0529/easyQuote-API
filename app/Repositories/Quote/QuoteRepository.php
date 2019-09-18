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
    QuoteTemplate\TemplateField
};
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    FindQuoteTemplateRequest
};
use Illuminate\Support\Collection;

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
            $quote = $user->quotes()->whereId($request->quote_id)->firstOrNew([]);
        } else {
            $quote = $user->quotes()->make();
        }

        if(isset($quoteData)) {
            $quote->fill($quoteData);
        }

        $this->draftQuote($state, $quote);

        $this->storeQuoteFilesState($state, $quote);
        $this->attachColumnsToFields($state, $quote);
        $this->markRowsAsSelectedOrUnSelected($state, $quote);

        return $quote->load('quoteFiles', 'fieldColumn', 'quoteTemplate.templateFields', 'rowsData');
    }

    public function findOrNew(string $id)
    {
        return $this->quote->whereId($id)->firstOrNew();
    }

    public function find(string $id)
    {
        $user = request()->user();

        $quote = $user->quotes()->whereId($id)
            ->with(
                'fieldColumn', 'rowsData.columnsData',
                'quoteTemplate.templateFields.templateFieldType'
            )
            ->first();

        return $quote;
    }

    public function getDrafted()
    {
        $user = request()->user();
        return $user->quotes()->drafted()->get();
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

    private function draftQuote(Collection $state, Quote $quote): Quote
    {
        if($state->has('save') && $state->get('save')) {
            $quote->markAsNotDrafted();
        } else {
            $quote->markAsDrafted();
        }

        return $quote;
    }

    private function markRowsAsSelectedOrUnSelected(
        Collection $state,
        Quote $quote,
        string $markAsSelected = 'markAsSelected',
        string $markAsUnSelected = 'markAsUnSelected'
    ): Quote {
        if(!isset($state['quote_data']['selected_rows'])) {
            return $quote;
        };

        if(isset($state['quote_data']['selected_rows_is_rejected'])) {
            $markAsSelected = 'markAsUnSelected';
            $markAsUnSelected = 'markAsSelected';
        };

        $selectedRowsIds = data_get($state, 'quote_data.selected_rows');

        $notSelectedRows = $quote->rowsData()->whereNotIn('imported_rows.id', $selectedRowsIds);

        $notSelectedRows->each(function ($importedRow) use ($markAsUnSelected) {
            $importedRow->{$markAsUnSelected}();
        });

        $selectedRows = $quote->rowsData()->whereIn('imported_rows.id', $selectedRowsIds);

        $selectedRows->each(function ($importedRow) use ($markAsSelected) {
            $importedRow->{$markAsSelected}();
        });

        return $quote;
    }

    private function attachColumnsToFields(Collection $state, Quote $quote): Quote
    {
        if(!isset($state['quote_data']['field_column'])) {
            return $quote;
        }

        collect(data_get($state, 'quote_data.field_column'))->each(function ($relation) use ($quote) {
            ['template_field_id' => $templateFieldId, 'importable_column_id' => $importableColumnId] = $relation;

            $templateField = $this->templateField->whereId($templateFieldId)->first();

            if(!isset($importableColumnId)) {
                $quote->detachTemplateField($templateField);

                return true;
            }

            $importableColumn = $this->importableColumn->whereId($importableColumnId)->first();

            $quote->attachColumnToField($templateField, $importableColumn);
        });

        return $quote;
    }

    private function storeQuoteFilesState(Collection $state, Quote $quote): Quote
    {
        if(!isset($state['quote_data']['files'])) {
            return $quote;
        }

        $stateFiles = collect(data_get($state, 'quote_data.files'));

        $stateFiles->each(function ($fileId) use ($quote) {
            $quoteFile = $this->quoteFile->whereId($fileId)->first();
            $quoteFile->quote()->associate($quote)->save();
        });

        return $quote;
    }

    private function filterState(StoreQuoteStateRequest $request): Collection
    {
        $stateModels = [
            'quote_data.type',
            'quote_data.company_id',
            'quote_data.vendor_id',
            'quote_data.country_id',
            'quote_data.language_id',
            'quote_data.files',
            'quote_data.field_column',
            'quote_data.quote_template_id',
            'quote_data.customer_id',
            'quote_data.selected_rows',
            'quote_data.selected_rows_is_rejected',
            'save'
        ];

        return collect($request->only($stateModels));
    }
}
