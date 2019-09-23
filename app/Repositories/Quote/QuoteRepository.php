<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories \ {
    Quote\QuoteRepositoryInterface,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository
};
use App\Models \ {
    Company,
    Vendor,
    Quote\Quote,
    Quote\Margin\CountryMargin,
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteFile\DataSelectSeparator,
    QuoteTemplate\TemplateField
};
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    ReviewAppliedMarginRequest
};
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Setting;

class QuoteRepository implements QuoteRepositoryInterface
{
    protected $quote;

    protected $quoteFile;

    protected $quoteTemplate;

    protected $templateField;

    protected $importableColumn;

    protected $company;

    protected $dataSelectSeparator;

    protected $defaultPage;

    private $importableColumnsByName;

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
        $this->defaultPage = Setting::get('parser.default_page');

        $importableColumnsByName = $this->importableColumn->system()->ordered()->get(['id', 'name'])->toArray();
        $importableColumnsByName = collect($importableColumnsByName)->mapWithKeys(function ($value) {
            return [$value['name'] => $value['id']];
        });

        $this->importableColumnsByName = $importableColumnsByName;
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
        $this->detachScheduleIfRequested($state, $quote);
        $this->attachColumnsToFields($state, $quote);
        $this->markRowsAsSelectedOrUnSelected($state, $quote);
        $this->setMargin($quote, $request->margin);

        $quote = $quote->loadJoins()->setAppends(['field_column']);

        return $quote;
    }

    public function findOrNew(string $id)
    {
        return $this->quote->whereId($id)->firstOrNew();
    }

    public function find(string $id)
    {
        $user = request()->user();

        $quote = $user->quotes()->whereId($id)
            ->withJoins()
            ->first()
            ->setAppends(['field_column']);

        return $quote;
    }

    public function getDrafted()
    {
        $user = request()->user();
        return $user->quotes()->drafted()->ordered()->limit(5)->get();
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

    public function step2(MappingReviewRequest $request)
    {
        $user = request()->user();

        $quote = $user->quotes()->whereId($request->quote_id)->firstOrFail();

        $rowsData = $quote->rowsDataByColumns()->get();

        $rowsData = $this->transformRowsData($rowsData, $quote);

        return $rowsData;
    }

    public function step4(ReviewAppliedMarginRequest $request)
    {
        $user = request()->user();

        $quote = $user->quotes()->with('rowsDataByColumns')->whereId($request->quote_id)->firstOrFail();
        $quote = $this->applyMargin($quote);

        $rowsData = $quote->rowsDataByColumns;

        $rowsData = $this->transformRowsData($rowsData, $quote);

        return $rowsData;
    }

    public function setMargin(Quote $quote, $attributes)
    {
        if(!isset($attributes) || !is_array($attributes) || empty($attributes)) {
            return null;
        }

        if(isset($attributes['delete']) && $attributes['delete']) {
            return $quote->deleteCountryMargin();
        }

        return $quote->createCountryMargin($attributes);
    }

    private function transformRowsData(EloquentCollection $rowsData, $quote)
    {
        $rowsData->transform(function ($row) use ($quote) {
            $columnsData = $row->columnsData->mapWithKeys(function ($column) use ($quote) {
                if(!isset($column->value) || mb_strlen(trim($column->value)) === 0) {
                    $value = $column->is_default_enabled ? $column->default_value : $column->value;
                } else {
                    $value = $column->value;
                }

                $value = $quote->customer->handleColumnValue($value, $column->importableColumn);

                return [$column->template_field_name => $value];
            });

            return collect($row->only('id', 'is_selected'))->merge($columnsData);
        });

        return $rowsData;
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
            $templateFieldId = $relation['template_field_id'] ?? null;
            $importableColumnId = $relation['importable_column_id'] ?? null;
            $attributes = collect($relation)->except(['template_field_id', 'importable_column_id'])->all();

            if(!isset($templateFieldId)) {
                return true;
            }

            $templateField = $this->templateField->whereId($templateFieldId)->first();

            if(!isset($importableColumnId)) {
                $quote->detachTemplateField($templateField);
                return true;
            }

            $importableColumn = $this->importableColumn->whereId($importableColumnId)->first();

            $quote->attachColumnToField($templateField, $importableColumn, $attributes);
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

            if($quote->quoteFiles()->whereId($fileId)->exists()) {
                return true;
            }

            $quoteFile->quote()->associate($quote)->save();
        });

        return $quote;
    }

    private function detachScheduleIfRequested(Collection $state, Quote $quote): Quote
    {
        if(!isset($state['quote_data']['detach_schedule']) || !$state['quote_data']['detach_schedule']) {
            return $quote;
        }

        $quote->quoteFiles()->paymentSchedules()->delete();

        return $quote;
    }

    private function detachQuoteFiles(Collection $state, Quote $quote): Quote
    {
        if(!isset($state['quote_data']['detach_files'])) {
            return $quote;
        }

        $detachableFiles = collect(data_get($state, 'quote_data.detach_files'));

        $detachableFiles->each(function ($fileId) use ($quote) {
            $quoteFile = $quote->quoteFiles()->whereId($fileId)->first();

            if(!isset($quoteFile)) {
                return true;
            }

            $quoteFile->columnsData()->delete();
            $quoteFile->rowsData()->delete();

            $quoteFile->delete();
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
            'quote_data.last_drafted_step',
            'quote_data.detach_schedule',
            'save'
        ];

        return collect($request->only($stateModels));
    }

    private function applyMargin(Quote $quote, bool $mounthly = true)
    {
        $countryMargin = $quote->countryMargin;

        if(!isset($countryMargin)) {
            return $quote;
        }

        $quote->rowsDataByColumns = $quote->rowsDataByColumns->map(function ($row) use ($countryMargin, $mounthly) {
            $dateFromColumn = $this->getColumn($row->columnsData, 'date_from');
            $dateToColumn = $this->getColumn($row->columnsData, 'date_to');
            $priceColumn = $this->getColumn($row->columnsData, 'price');

            if($mounthly) {
                $priceColumn->value = $countryMargin->calculate($priceColumn->value);
            } else {
                $priceColumn->value = $countryMargin->calculate($priceColumn->value, $dateFromColumn->value, $dateToColumn->value);
            }

            return $row;
        });

        return $quote;
    }

    private function getColumn(EloquentCollection $collection, string $name)
    {
        $importableColumns = $this->importableColumnsByName;

        if(!$importableColumns->has($name)) {
            return null;
        }

        return $collection->where('importable_column_id', $importableColumns->get($name))->first();
    }
}
