<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories \ {
    Quote\QuoteRepositoryInterface,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository,
    QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
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
use App\Models\QuoteFile\ImportedColumn;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Schema;
use Setting, Arr, Closure, DB, Cache;

class QuoteRepository implements QuoteRepositoryInterface
{
    protected $quote;

    protected $quoteService;

    protected $quoteFile;

    protected $quoteFileRepository;

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
        QuoteService $quoteService,
        QuoteFile $quoteFile,
        QuoteTemplateRepository $quoteTemplate,
        QuoteFileRepository $quoteFileRepository,
        QuoteDiscount $quoteDiscount,
        TemplateField $templateField,
        ImportableColumn $importableColumn,
        Company $company,
        Vendor $vendor,
        DataSelectSeparator $dataSelectSeparator,
        Elasticsearch $search,
        MultiYearDiscount $multiYearDiscount,
        PrePayDiscount $prePayDiscount,
        PromotionalDiscount $promotionalDiscount,
        SND $snd
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->quoteFileRepository = $quoteFileRepository;
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

        Cache::forget("quote_list_price:{$quote->id}");

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

    public function getWithModifications(string $id)
    {
        $quote = $this->find($id);
        $quote->list_price = Cache::rememberForever("quote_list_price:{$quote->id}", function () use ($quote) {
            $quote->computableRows = $this->createDefaultData($quote->selectedRowsDataByColumns, $quote);
            $quote->computableRows = $this->setDefaultValues($quote->computableRows, $quote);
            $quote = $this->interactWithModels($quote);
            $quote->makeHidden(['computableRows']);
            return $this->quoteService->countTotalPrice($quote);
        });

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
        $activated = $this->filterQuery($this->draftedQuery()->activated());
        $deactivated = $this->filterQuery($this->draftedQuery()->deactivated());

        return $activated->union($deactivated)->apiPaginate();
    }

    public function searchDrafted(string $query = ''): Paginator
    {
        $items = $this->searchOnElasticsearch($query);

        $user = request()->user();

        $activated = $this->buildQuery($items, function ($query) use ($user) {
            $query = $query->drafted()->where('user_id', $user->id)->with('customer', 'company')->activated();
            return $this->filterQuery($query);
        });
        $deactivated = $this->buildQuery($items, function ($query) use ($user) {
            $query = $query->drafted()->where('user_id', $user->id)->with('customer', 'company')->deactivated();
            return $this->filterQuery($query);
        });

        return $activated->union($deactivated)->apiPaginate();
    }

    public function submittedQuery(): Builder
    {
        $user = request()->user();
        $query = $user->quotes()->submitted()->with('customer', 'company')->getQuery();

        return $query;
    }

    public function getSubmitted(string $id)
    {
        $quote = $this->submittedQuery()->whereId($id)->firstOrFail();

        return $quote;
    }

    public function allSubmitted()
    {
        $activated = $this->filterQuery($this->submittedQuery()->activated());
        $deactivated = $this->filterQuery($this->submittedQuery()->deactivated());

        return $activated->union($deactivated)->apiPaginate();
    }

    public function searchSubmitted(string $query = ''): Paginator
    {
        $items = $this->searchOnElasticsearch($query);

        $user = request()->user();

        $activated = $this->buildQuery($items, function ($query) use ($user) {
            $query = $query->submitted()->where('user_id', $user->id)->with('customer', 'company')->activated();
            return $this->filterQuery($query);
        });
        $deactivated = $this->buildQuery($items, function ($query) use ($user) {
            $query = $query->submitted()->where('user_id', $user->id)->with('customer', 'company')->deactivated();
            return $this->filterQuery($query);
        });

        return $activated->union($deactivated)->apiPaginate();
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

    public function deleteSubmitted(string $id)
    {
        $user = request()->user();
        return $user->quotes()->submitted()->whereId($id)->firstOrFail()->delete();
    }

    public function deactivateSubmitted(string $id)
    {
        $user = request()->user();
        return $user->quotes()->submitted()->whereId($id)->firstOrFail()->deactivate();
    }

    public function activateSubmitted(string $id)
    {
        $user = request()->user();
        return $user->quotes()->submitted()->whereId($id)->firstOrFail()->activate();
    }

    public function copy(string $id)
    {
        $quote = $this->submittedQuery()
            ->with('user', 'company', 'vendor', 'country', 'countryMargin', 'discounts', 'customer', 'quoteTemplate')
            ->whereId($id)
            ->firstOrFail();

        $replicatedQuote = $quote->replicate();
        $replicatedQuote->push();

        /**
         * Mapping Replication
         */
        $quote->templateFields()->withPivot('is_default_enabled', 'importable_column_id')->get()
            ->each(function ($templateField) use ($replicatedQuote) {
                $attributes = $templateField->pivot->only('is_default_enabled', 'importable_column_id');
                $replicatedQuote->templateFields()->attach([$templateField->id => $attributes]);
            });

        $priceList = $quote->quoteFiles()->priceLists()->first();
        if(isset($priceList)) {
            $replicatedPriceList = $this->quoteFileRepository->replicatePriceList($priceList);
            $replicatedQuote->quoteFiles()->save($replicatedPriceList);
        }

        $schedule = $quote->quoteFiles()->paymentSchedules()->first();
        if(isset($schedule)) {
            $replicatedSchedule = $schedule->replicate();
            $scheduleData = $schedule->scheduleData()->first();
            if(isset($scheduleData)) {
                $replicatedScheduleData = $scheduleData->replicate();
                $replicatedSchedule->scheduleData()->associate($replicatedScheduleData);
                $replicatedQuote->quoteFiles()->save($replicatedSchedule);
            }
        }

        return $replicatedQuote->unSubmit();
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

    public function review(string $quoteId)
    {
        $quote = $this->find($quoteId);

        $quote->computableRows = $this->createDefaultData($quote->selectedRowsDataByColumns, $quote);
        $quote->computableRows = $this->setDefaultValues($quote->computableRows, $quote);

        /**
         * Possible Interactions with Margins and Discounts
         */
        $quote = $this->interactWithModels($quote);

        /**
         * Calculate List Price if not calculated after interactions
         */
        if($quote->list_price === 0.00) {
            $quote->list_price = $this->quoteService->countTotalPrice($quote);
        }

        /**
         * Calculate Mounthly Prices based on Coverage Periods
         */
        $this->quoteService->transformPricesBasedOnCoverages($quote);

        $equipment_address = $quote->customer->hardwareAddresses()->first()->address_1 ?? null;
        $hardware_contact = $quote->customer->hardwareContacts()->first();
        $hardware_contact_name = $hardware_contact->contact_name ?? null;
        $hardware_contact_phone = $hardware_contact->phone ?? null;

        $software_address = $quote->customer->softwareAddresses()->first()->address_1 ?? null;
        $software_contact = $quote->customer->softwareContacts()->first();
        $software_contact_name = $software_contact->contact_name ?? null;
        $software_contact_phone = $software_contact->phone ?? null;

        $coverage_period = "{$quote->customer->support_start} to {$quote->customer->support_end}";

        $quote = $this->addBuyPriceColumn($quote);
        $rows = $this->transformRowsData($quote->computableRows);

        $pages = collect();

        $first_page = [
            'id' => $quote->id,
            'template_name' => $quote->quoteTemplate->name,
            'customer_name' => $quote->customer->name,
            'company_name' => $quote->company->name,
            'company_logo' => $quote->company->logo,
            'vendor_name' => $quote->vendor->name,
            'vendor_logo' => $quote->vendor->logo,
            'support_start' => $quote->customer->support_start,
            'support_end' => $quote->customer->support_end,
            'valid_until' => $quote->customer->valid_until,
            'quotation_number' => $quote->customer->rfq,
            'service_level' => $quote->customer->service_level,
            'list_price' => $quote->list_price_formatted,
            'applicable_discounts' => $quote->applicable_discounts_formatted,
            'final_price' => $quote->final_price,
            'payment_terms' => $quote->customer->payment_terms,
            'invoicing_terms' => $quote->customer->invoicing_terms,
            'full_name' => $quote->user->full_name,
            'date' => $quote->updated_at
        ];

        $data_pages = [
            'pricing_document' => $quote->pricing_document,
            'service_agreement_id' => $quote->service_agreement_id,
            'system_handle' => $quote->system_handle,
            'equipment_address' => $equipment_address,
            'hardware_contact' => $hardware_contact_name,
            'hardware_phone' => $hardware_contact_phone,
            'software_address' => $software_address,
            'software_contact' => $software_contact_name,
            'software_phone' => $software_contact_phone,
            'coverage_period' => $coverage_period,
            'rows' => $rows
        ];

        $last_page = [
            'additional_details' => $quote->additional_details
        ];

        $payment_schedule = [
            'data' => $quote->scheduleData->value
        ];

        $pages = $pages->merge(compact('first_page', 'data_pages', 'last_page', 'payment_schedule'));


        return $pages;
    }

    private function interactWithModels(Quote $quote)
    {
        /**
         * Possible interaction with existing Country Margin
         */
        $quote = $this->quoteService->interact($quote, $quote->countryMargin);

        /**
         * Possible interaction with existing Discounts
         */
        $multiYearDiscount = $quote->discounts()->whereHasMorph('discountable', MultiYearDiscount::class)->first();
        $promotionalDiscount = $quote->discounts()->whereHasMorph('discountable', PromotionalDiscount::class)->first();
        $snDiscount = $quote->discounts()->whereHasMorph('discountable', SND::class)->first();
        $prePayDiscount = $quote->discounts()->whereHasMorph('discountable', PrePayDiscount::class)->first();

        $quote = $this->quoteService->interact($quote, $multiYearDiscount);
        $quote = $this->quoteService->interact($quote, $promotionalDiscount);
        $quote = $this->quoteService->interact($quote, $snDiscount);
        $quote = $this->quoteService->interact($quote, $prePayDiscount);

        return $quote;
    }

    private function addBuyPriceColumn(Quote $quote)
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        $mapping = $quote->mapping;
        $margin_percentage = $quote->margin_percentage;

        $quote->computableRows->transform(function ($row) use ($mapping, $margin_percentage) {
            $priceColumn = $this->quoteService->getRowColumn($mapping, $row->columnsData, 'price');
            $value = round((float) $priceColumn->value - ((float) $priceColumn->value * ($margin_percentage / 100)), 2);

            $buyPriceColumn = $row->columnsData()->make(compact('value'));
            $buyPriceColumn->template_field_name = 'buy_price';


            $row->columnsData->push($buyPriceColumn);

            return $row;
        });

        return $quote;
    }

    private function transformRowsData(EloquentCollection $rowsData)
    {
        $rowsData->transform(function ($row) {
            $columnsData = $row->columnsData->mapWithKeys(function ($column) {
                $value = trim(preg_replace('/[\h]/u', ' ', $column->value));

                if($column->template_field_name === 'price') {
                    $value = number_format((float) $value, 2);
                }

                return [$column->template_field_name => $value];
            });
            return collect($row->only('id', 'is_selected'))->merge($columnsData);
        });

        return $rowsData;
    }

    private function setDefaultValues(EloquentCollection $rowsData, Quote $quote)
    {
        $mapping = $quote->mapping;

        $rowsData->transform(function ($row) use ($quote, $mapping) {
            $dateFrom = $this->quoteService->getRowColumn($mapping, $row->columnsData, 'date_from');
            $dateTo = $this->quoteService->getRowColumn($mapping, $row->columnsData, 'date_to');

            if($dateFrom instanceof ImportedColumn) {
                $dateFrom->value = $quote->customer->handleColumnValue(
                    $dateFrom->value,
                    'date_from',
                    $dateFrom->is_default_enabled
                );
            }

            if($dateTo instanceof ImportedColumn) {
                $dateTo->value = $quote->customer->handleColumnValue(
                    $dateTo->value,
                    'date_to',
                    $dateTo->is_default_enabled
                );
            }

            return $row;
        });

        return $rowsData;
    }

    private function draftQuote(Collection $state, Quote $quote): Quote
    {
        if(!$state->has('save') || !$state->get('save')) {
            $quote->markAsDrafted();
            return $quote;
        }

        if($state->get('save')) {
            $quote->submit();
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

        if(isset($state['quote_data']['selected_rows_is_rejected']) && $state['quote_data']['selected_rows_is_rejected']) {
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
            'quote_data.pricing_document',
            'quote_data.service_agreement_id',
            'quote_data.system_handle',
            'quote_data.additional_details',
            'quote_data.checkbox_status',
            'quote_data.closing_date',
            'quote_data.additional_notes',
            'quote_data.calculate_list_price',
            'quote_data.buy_price',
            'save'
        ];

        return collect($request->only($stateModels));
    }

    private function createDefaultData(EloquentCollection $rowsData, Quote $quote)
    {
        $defaultTemplateFields = $quote->defaultTemplateFields()->get();

        $rowsData->transform(function ($row) use ($defaultTemplateFields, $quote) {
            foreach ($defaultTemplateFields as $templateField) {
                $value = $quote->customer->handleColumnValue(null, $templateField->name, true);
                $columnData = $row->columnsData()->make(compact('value'));
                $columnData->template_field_name = $templateField->name;
                $columnData->importable_column_id = $templateField->systemImportableColumn->id;

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
                \App\Http\Query\Quote\OrderByCompleteness::class
            ])
            ->thenReturn();
    }
}
