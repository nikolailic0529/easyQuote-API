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
    MappingReviewRequest
};
use Illuminate\Support\Collection;
use Elasticsearch\Client as Elasticsearch;
use Setting, Cache;

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
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        $user = $request->user();
        $state = collect($request->validated());
        $quoteData = $state->get('quote_data');

        if($request->has('quote_id')) {
            $quote = $user->quotes()->whereId($request->quote_id)->firstOrFail();
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
        $quote = $this->quote->userCollaboration()->whereId($id)->withJoins()->firstOrFail()->appendJoins();

        return $quote;
    }

    public function getWithModifications(string $id)
    {
        $quote = $this->find($id);

        $quote->list_price = $quote->countTotalPrice();

        return $quote;
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

        return $this->mappingReviewData($quote);
    }

    public function setMargin(Quote $quote, $attributes)
    {
        if(!isset($attributes) || !is_array($attributes) || empty($attributes)) {
            return null;
        }

        if(isset($attributes['delete']) && $attributes['delete']) {
            return $quote->deleteCountryMargin();
        }

        unset($quote->computableRows, $quote->list_price);

        return $quote->createCountryMargin($attributes);
    }

    public function setDiscounts(Quote $quote, $attributes, $detach)
    {
        if((bool) $detach === true) {
            $quote->discounts()->detach();

            return $quote;
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

        if($quote->custom_discount > 0) {
            $quote->resetCustomDiscount();
        }

        return $quote->load('discounts');
    }

    public function discounts(string $id)
    {
        $quote = $this->find($id);

        $multi_year = $this->multiYearDiscount
            ->userCollaboration()
            ->quoteAcceptable($quote)
            ->get();
        $pre_pay = $this->prePayDiscount
            ->userCollaboration()
            ->quoteAcceptable($quote)
            ->get();
        $promotions = $this->promotionalDiscount
            ->userCollaboration()
            ->quoteAcceptable($quote)
            ->get();
        $snd = $this->snd
            ->userCollaboration()
            ->quoteAcceptable($quote)
            ->get();

        return compact('multi_year', 'pre_pay', 'promotions', 'snd');
    }

    public function review(string $quoteId)
    {
        $quote = $this->find($quoteId);

        $quote->computableRows = $quote->calculate_list_price ? $quote->rowsDataByColumnsCalculated(true)->get() : $quote->rowsDataByColumns(true)->get();

        /**
         * Possible Interactions with Margins and Discounts
         */
        $this->interactWithModels($quote);

        /**
         * Calculate List Price if not calculated after interactions
         */
        if(((float) $quote->list_price) === 0.00) {
            $quote->list_price = $quote->countTotalPrice();
        }

        /**
         * Calculate Schedule Total Prices based on Margin Percentage
         */
        $this->quoteService->calculateSchedulePrices($quote);

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
            'rows' => $quote->computableRows
        ];

        $last_page = [
            'additional_details' => $quote->additional_details
        ];

        $payment_schedule = [
            'period' => $coverage_period,
            'data' => $quote->scheduleData->value
        ];

        $pages = $pages->merge(compact('first_page', 'data_pages', 'last_page', 'payment_schedule'));


        return $pages;
    }

    public function mappingReviewData(Quote $quote, $clearCache = null)
    {
        $cacheKey = "mapping-review-data:{$quote->id}";

        if(isset($clearCache) && $clearCache) {
            Cache::forget($cacheKey);
        }

        return Cache::rememberForever($cacheKey, function () use ($quote) {
            return $quote->rowsDataByColumns()->get();
        });
    }

    public function rows(string $id, string $query = ''): Collection
    {
        return $this->find($id)->rowsDataByColumnsGroupable($query)->get();
    }

    private function interactWithModels(Quote $quote)
    {
        /**
         * Possible interaction with Margin percentage.
         */
        $this->quoteService->interactWithMargin($quote);

        /**
         * Possible interaction with Discounts.
         */
        $this->quoteService->interact(
            $quote,
            collect($quote->discounts)->prepend($quote->custom_discount)
        );

        return $quote;
    }

    private function addBuyPriceColumn(Quote $quote)
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        $margin_percentage = $quote->margin_percentage;

        $quote->computableRows->transform(function ($row) use ($margin_percentage) {
            $row->buy_price = round($row->price - ($row->price * ($margin_percentage / 100)), 2);
            return $row;
        });

        return $quote;
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

        /**
         * Recalculate User's Margin Percentage After Select Rows
         */
        $this->calculateMarginPercentage($quote);

        /**
         * Clear Cache Mapping Review Data when Selected Rows was changed
         */
        Cache::forget("mapping-review-data:{$quote->id}");

        return $quote;
    }

    private function calculateMarginPercentage(Quote $quote): Quote
    {
        $quote->list_price = $quote->countTotalPrice();

        if(((float) $quote->list_price) === 0.00) {
            $quote->margin_percentage = 0;
            $quote->save();

            return $quote;
        }

        $quote->margin_percentage = round((($quote->list_price - $quote->buy_price) / $quote->list_price) * 100, 2);
        $quote->save();

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

        /**
         * Clear Cache Mapping Review Data when Mapping is changed
         */
        Cache::forget("mapping-review-data:{$quote->id}");

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
}
