<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories \ {
    Quote\QuoteRepositoryInterface,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository
};
use App\Contracts\Services\QuoteServiceInterface as QuoteService;
use App\Models \ {
    Company,
    Vendor,
    Quote\Quote,
    Quote\Discount as QuoteDiscount,
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteFile\DataSelectSeparator,
    QuoteTemplate\TemplateField,
    Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND
};
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    ReviewAppliedMarginRequest
};
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use App\Builder\Pagination\Paginator;

use Illuminate\Pipeline\Pipeline;
use Setting, Arr, Closure;

class QuoteRepository implements QuoteRepositoryInterface
{
    protected $quote;

    protected $quoteFile;

    protected $quoteDiscount;

    protected $quoteTemplate;

    protected $templateField;

    protected $importableColumn;

    protected $company;

    protected $dataSelectSeparator;

    protected $defaultPage;

    protected $search;

    private $importableColumnsByName;

    public function __construct(
        Quote $quote,
        QuoteFile $quoteFile,
        QuoteTemplateRepository $quoteTemplate,
        QuoteDiscount $quoteDiscount,
        TemplateField $templateField,
        ImportableColumn $importableColumn,
        Company $company,
        Vendor $vendor,
        DataSelectSeparator $dataSelectSeparator,
        Elasticsearch $search,
        QuoteService $quoteService,
        MultiYearDiscount $multiYearDiscount,
        PrePayDiscount $prePayDiscount,
        PromotionalDiscount $promotionalDiscount,
        SND $snd
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->quoteDiscount = $quoteDiscount;
        $this->quoteTemplate = $quoteTemplate;
        $this->templateField = $templateField;
        $this->importableColumn = $importableColumn;
        $this->company = $company;
        $this->vendor = $vendor;
        $this->dataSelectSeparator = $dataSelectSeparator;
        $this->defaultPage = Setting::get('parser.default_page');
        $this->search = $search;
        $this->quoteService = $quoteService;

        /**
         * Discounts
         */
        $this->multiYearDiscount = $multiYearDiscount;
        $this->prePayDiscount = $prePayDiscount;
        $this->promotionalDiscount = $promotionalDiscount;
        $this->snd = $snd;

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
        $this->setDiscounts($quote, $request->discounts, $request->discounts_detach);

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
        $quote = $user->quotes()->whereId($id)->withJoins()->firstOrFail()->appendJoins();

        return $quote;
    }

    public function draftedQuery(): Builder
    {
        $user = request()->user();
        $query = $user->quotes()->drafted()->with('customer', 'company')->getQuery();

        return $query;
    }

    public function getDrafted(string $id)
    {
        $quote = $this->draftedQuery()->whereId($id)->firstOrFail();

        return $quote;
    }

    public function allDrafted()
    {
        $query = $this->draftedQuery();

        return $this->filterQuery($query)->apiPaginate();
    }

    public function searchDrafted(string $query = ''): Paginator
    {
        $items = $this->searchOnElasticsearch($query);

        $user = request()->user();

        $query = $this->buildQuery($items, function ($query) use ($user) {
            $query = $query->drafted()->where('user_id', $user->id)->with('customer', 'company');
            return $this->filterQuery($query);
        });

        return $query->apiPaginate();
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

        /**
         * Re-order Companies (Support Warehouse on the 1st place)
         */
        $companies = $companies->sortByDesc(function ($company) {
            return $company->vat === 'GB758501125';
        })->values();

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
        $quote = $this->find($request->quote_id);

        $rowsData = $this->createDefaultData($quote->rowsDataByColumns, $quote);
        $rowsData = $this->setDefaultValues($rowsData, $quote);
        $rowsData = $this->transformRowsData($rowsData);

        return $rowsData;
    }

    public function step4(ReviewAppliedMarginRequest $request)
    {
        $quote = $this->find($request->quote_id);

        $quote->computableRows = $this->createDefaultData($quote->rowsDataByColumns, $quote);
        $quote->computableRows = $this->setDefaultValues($quote->computableRows, $quote);
        $quote->computableRows = $this->transformRowsData($quote->computableRows);

        return $quote->computableRows;
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

    public function setDiscounts(Quote $quote, $attributes, $detach)
    {
        if(isset($detach) && $detach) {
            $quote->discounts()->detach();

            return $quote->load('discounts');
        }

        if(!isset($attributes) || !is_array($attributes) || empty($attributes)) {
            return null;
        }

        $quoteDiscounts = collect($attributes)->mapWithKeys(function ($discount) {
            $id = $this->quoteDiscount->where('discountable_id', $discount['id'])->firstOrFail()->id;
            $duration = $discount['duration'] ?? null;

            return [$id => compact('duration')];
        });

        $quote->discounts()->sync($quoteDiscounts);

        return $quote->load('discounts');
    }

    public function deleteDrafted(string $id)
    {
        $user = request()->user();
        return $user->quotes()->drafted()->whereId($id)->firstOrFail()->delete();
    }

    public function deactivateDrafted(string $id)
    {
        $user = request()->user();
        return $user->quotes()->drafted()->whereId($id)->firstOrFail()->deactivate();
    }

    public function activateDrafted(string $id)
    {
        $user = request()->user();
        return $user->quotes()->drafted()->whereId($id)->firstOrFail()->activate();
    }

    public function discounts(string $id)
    {
        $user = request()->user();
        $quote = $user->quotes()->whereId($id)->firstOrFail();

        $multi_year = $this->multiYearDiscount
            ->where('user_id', $user->id)
            ->quoteAcceptable($quote)
            ->get();
        $pre_pay = $this->prePayDiscount
            ->where('user_id', $user->id)
            ->quoteAcceptable($quote)
            ->get();
        $promotions = $this->promotionalDiscount
            ->where('user_id', $user->id)
            ->quoteAcceptable($quote)
            ->get();
        $snd = $this->snd
            ->where('user_id', $user->id)
            ->quoteAcceptable($quote)
            ->get();

        return compact('multi_year', 'pre_pay', 'promotions', 'snd');
    }

    private function transformRowsData(EloquentCollection $rowsData)
    {
        $rowsData->transform(function ($row) {
            $columnsData = $row->columnsData->mapWithKeys(function ($column) {
                return [$column->template_field_name => $column->value];
            });
            return collect($row->only('id', 'is_selected'))->merge($columnsData);
        });

        return $rowsData;
    }

    private function setDefaultValues(EloquentCollection $rowsData, Quote $quote)
    {
        $rowsData->transform(function ($row) use ($quote) {
            $row->columnsData->transform(function ($column) use ($quote) {
                $column->value = $quote->customer->handleColumnValue(
                    $column->value,
                    $column->importableColumn,
                    $column->is_default_enabled
                );
                return $column;
            });
            return $row;
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

            if(!isset($importableColumnId) && !(isset($attributes['is_default_enabled']) && $attributes['is_default_enabled'])) {
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

    private function createDefaultData(EloquentCollection $rowsData, Quote $quote)
    {
        $defaultTemplateFields = $quote->defaultTemplateFields()->get();

        $rowsData->transform(function ($row) use ($defaultTemplateFields, $quote) {
            foreach ($defaultTemplateFields as $templateField) {
                $value = $quote->customer->handleColumnValue(null, $templateField->systemImportableColumn, true);
                $columnData = $row->columnsData()->make(compact('value'));
                $columnData->template_field_name = $templateField->name;

                $row->columnsData->push($columnData);
            }
            return $row;
        });

        return $rowsData;
    }

    private function searchOnElasticsearch(string $query = '')
    {
        $body = [
            'query' => [
                'multi_match' => [
                    'fields' => [
                        'customer.name', 'customer.valid_until', 'customer.support_start', 'customer.support_end', 'customer.rfq',
                        'company.name', 'type', 'created_at'
                    ],
                    'type' => 'phrase_prefix',
                    'query' => $query
                ]
            ]
        ];

        $items = $this->search->search([
            'index' => $this->quote->getSearchIndex(),
            'type' => $this->quote->getSearchType(),
            'body' => $body
        ]);

        return $items;
    }

    private function buildQuery(array $items, Closure $scope = null): Builder
    {
        $ids = Arr::pluck($items['hits']['hits'], '_id');

        $query = $this->quote->query();

        if(is_callable($scope)) {
            $query = call_user_func($scope, $query) ?? $query;
        }

        return $query->whereIn('quotes.id', $ids);
    }

    private function filterQuery(Builder $query)
    {
        return app(Pipeline::class)
            ->send($query)
            ->through([
                \App\Http\Query\DefaultOrderBy::class,
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\Quote\OrderByName::class,
                \App\Http\Query\Quote\OrderByCompanyName::class,
                \App\Http\Query\Quote\OrderByRfq::class,
                \App\Http\Query\Quote\OrderByValidUntil::class,
                \App\Http\Query\Quote\OrderBySupportStart::class,
                \App\Http\Query\Quote\OrderBySupportEnd::class,
                \App\Http\Query\Quote\OrderByCompleteness::class,
                \App\Http\Query\DefaultGroupByActivation::class
            ])
            ->thenReturn();
    }
}
