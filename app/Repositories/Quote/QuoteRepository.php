<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\{
    Quote\QuoteRepositoryInterface,
    Quote\Margin\MarginRepositoryInterface as MarginRepository,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository,
    QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
};
use App\Contracts\Services\QuoteServiceInterface as QuoteService;
use App\Models\{
    Company,
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
use App\Http\Requests\{
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Http\Resources\QuoteResource;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Webpatser\Uuid\Uuid;
use Cache;

class QuoteRepository implements QuoteRepositoryInterface
{
    protected $quote;

    protected $quoteService;

    protected $quoteFile;

    protected $quoteFileRepository;

    protected $margin;

    protected $quoteDiscount;

    protected $quoteTemplate;

    protected $templateField;

    protected $importableColumn;

    protected $company;

    protected $dataSelectSeparator;

    public function __construct(
        Quote $quote,
        QuoteService $quoteService,
        QuoteFile $quoteFile,
        QuoteTemplateRepository $quoteTemplate,
        QuoteFileRepository $quoteFileRepository,
        MarginRepository $margin,
        QuoteDiscount $quoteDiscount,
        TemplateField $templateField,
        ImportableColumn $importableColumn,
        Company $company,
        DataSelectSeparator $dataSelectSeparator,
        MultiYearDiscount $multiYearDiscount,
        PrePayDiscount $prePayDiscount,
        PromotionalDiscount $promotionalDiscount,
        SND $snd
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->quoteFileRepository = $quoteFileRepository;
        $this->margin = $margin;
        $this->quoteDiscount = $quoteDiscount;
        $this->quoteTemplate = $quoteTemplate;
        $this->templateField = $templateField;
        $this->importableColumn = $importableColumn;
        $this->company = $company;
        $this->dataSelectSeparator = $dataSelectSeparator;
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

        $quote = $request->has('quote_id')
            ? $this->find($request->quote_id)
            : $user->quotes()->make();

        filled($quoteData) && $quote->fill($quoteData);

        $this->draftOrSubmit($state, $quote);
        $this->storeQuoteFilesState($state, $quote);
        $this->detachScheduleIfRequested($state, $quote);
        $this->attachColumnsToFields($state, $quote);
        $this->markRowsAsSelectedOrUnSelected($state, $quote);
        $this->setMargin($quote, $request->margin);
        $this->setDiscounts($quote, $request->discounts, $request->discounts_detach);

        Cache::forget("quote_list_price:{$quote->id}");

        return $quote->only('id');
    }

    public function userQuery(): Builder
    {
        return $this->quote->query()->currentUser();
    }

    public function findOrNew(string $id)
    {
        return $this->userQuery()->whereId($id)->firstOrNew();
    }

    public function find(string $id)
    {
        $quote = $this->userQuery()->whereId($id)->withJoins()->firstOrFail()->appendJoins();

        return $quote;
    }

    public function preparedQuote(string $id)
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

    public function setMargin(Quote $quote, ?array $attributes): void
    {
        if (blank($attributes) || blank($quote->country_id) || blank($quote->vendor_id)) {
            return;
        }

        if (isset($attributes['delete']) && (bool) $attributes['delete']) {
            $quote->deleteCountryMargin();
            return;
        }

        $quote->countryMargin()->dissociate();

        $countryMargin = $this->margin->firstOrCreate($quote, $attributes);
        $quote->countryMargin()->associate($countryMargin);
        $quote->margin_data = array_merge($countryMargin->only('value', 'method', 'is_fixed'), ['type' => 'By Country']);
        $quote->type = $attributes['quote_type'];

        $quote->save();
    }

    public function setDiscounts(Quote $quote, $attributes, $detach): void
    {
        if ((bool) $detach === true) {
            $quote->resetCustomDiscount();
            $quote->discounts()->detach();
            return;
        }

        if (!isset($attributes) || !is_array($attributes) || empty($attributes)) {
            return;
        }

        $quoteDiscounts = collect($attributes)->mapWithKeys(function ($discount) {
            $id = $this->quoteDiscount->where('discountable_id', $discount['id'])->firstOrFail()->id;
            $duration = $discount['duration'] ?? null;
            return [$id => compact('duration')];
        });

        $quote->discounts()->sync($quoteDiscounts);

        if ($quote->custom_discount > 0) {
            $quote->resetCustomDiscount();
        }
    }

    public function discounts(string $id)
    {
        $quote = $this->find($id);

        $multi_year = $this->multiYearDiscount->quoteAcceptable($quote)->get();
        $pre_pay = $this->prePayDiscount->quoteAcceptable($quote)->get();
        $promotions = $this->promotionalDiscount->quoteAcceptable($quote)->get();
        $snd = $this->snd->quoteAcceptable($quote)->get();

        return compact('multi_year', 'pre_pay', 'promotions', 'snd');
    }

    public function review(string $quoteId)
    {
        $quote = $this->find($quoteId);

        $this->quoteService->prepareQuoteReview($quote);

        return (new QuoteResource($quote))->resolve()['quote_data'];
    }

    public function mappingReviewData(Quote $quote, $clearCache = null)
    {
        $cacheKey = "mapping-review-data:{$quote->id}";

        if (isset($clearCache) && $clearCache) {
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

    public function rowsGroups(string $id): Collection
    {
        $quote = $this->find($id);
        $grouped_rows = $quote->groupedRows()->get();
        $groups_meta = $quote->getGroupDescriptionWithMeta();

        return $grouped_rows->rowsToGroups('group_name', $groups_meta)->exceptEach('group_name');
    }

    public function submit(Quote $quote): void
    {
        $this->quoteService->export($quote);
        $submitted_data = (new QuoteResource($quote))->resolve();
        $quote->submit($submitted_data);
        $quote->customer->submit();
    }

    public function draft(Quote $quote): void
    {
        $quote->submitted_data = null;
        $quote->markAsDrafted();
    }

    public function draftOrSubmit(Collection $state, Quote $quote): void
    {
        if (!$state->has('save') || !$state->get('save')) {
            $this->draft($quote);
            return;
        }

        if ($state->get('save')) {
            $this->submit($quote);
        }
    }

    public function findGroupDescription(string $id, string $quote_id): Collection
    {
        $quote = $this->find($quote_id);

        $group_key = $quote->findGroupDescription($id);
        $group_key === false && abort(404, 'The Group Description is not found.');

        $group = collect($quote->group_description)->get($group_key);
        $groups_meta = $quote->getGroupDescriptionWithMeta(null, false, $group['name']);

        $group = $quote->groupedRows(null, false, $group['name'])->get()
            ->rowsToGroups('group_name', $groups_meta)->exceptEach('group_name')
            ->first();

        return $group;
    }

    public function createGroupDescription(StoreGroupDescriptionRequest $request, string $quote_id): Collection
    {
        $quote = $this->find($quote_id);

        $data = collect($request->validated());
        $group = $data->only(['name', 'search_text'])->prepend(Uuid::generate(4)->string, 'id');

        $rows = $data->get('rows', []);

        $quote->rowsData()->whereIn('imported_rows.id', $rows)
            ->update(['group_name' => $group->get('name')]);

        $quote->group_description = collect($quote->group_description)->push($group)->values();

        $quote->save();

        return $group;
    }

    public function updateGroupDescription(UpdateGroupDescriptionRequest $request, string $id, string $quote_id): bool
    {
        $quote = $this->find($quote_id);

        $group_description = collect($quote->group_description);

        $data = collect($request->validated());
        $group = $data->only(['name', 'search_text'])->toArray();
        $rows = $data->get('rows', []);

        $group_key = $quote->findGroupDescription($id);
        $group_key === false && abort(404, 'The Group Description is not found.');

        $updatableGroup = $group_description->get($group_key);

        $quote->rowsData()->whereGroupName($updatableGroup['name'])
            ->update(['group_name' => null]);

        $quote->rowsData()->whereIn('imported_rows.id', $rows)
            ->update(['group_name' => $group['name']]);

        $updatedGroup = array_merge($updatableGroup, $group);

        $quote->group_description = collect($quote->group_description)->put($group_key, $updatedGroup)->values();

        return $quote->save();
    }

    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, string $quote_id): bool
    {
        $quote = $this->find($quote_id);

        $group_description = collect($quote->group_description);

        $from_group_key = $quote->findGroupDescription($request->from_group_id);
        $to_group_key = $quote->findGroupDescription($request->to_group_id);

        ($from_group_key === false || $to_group_key === false) && abort(404, 'The From or To Group Description is not found.');

        $from_group = $group_description->get($from_group_key);
        $to_group = $group_description->get($to_group_key);

        return $quote->rowsData()->whereGroupName($from_group['name'])
            ->whereIn('imported_rows.id', $request->rows)
            ->update(['group_name' => $to_group['name']]);
    }

    public function deleteGroupDescription(string $id, string $quote_id): bool
    {
        $quote = $this->find($quote_id);

        $group_description = collect($quote->group_description);

        $group_key = $quote->findGroupDescription($id);
        $group_key === false && abort(404, 'The Group Description is not found.');

        $removableGroup = $group_description->get($group_key);

        $quote->rowsData()->whereGroupName($removableGroup['name'])
            ->update(['group_name' => null]);

        $group_description->forget($group_key);

        $quote->group_description = $group_description->isEmpty() ? null : $group_description->values();

        return $quote->save();
    }

    private function markRowsAsSelectedOrUnSelected(Collection $state, Quote $quote, bool $reject = false): void
    {
        if (!isset($state['quote_data']['selected_rows']) && !isset($state['quote_data']['selected_rows_is_rejected'])) {
            return;
        };

        if (isset($state['quote_data']['selected_rows_is_rejected']) && $state['quote_data']['selected_rows_is_rejected']) {
            $reject = true;
        };

        $selectedRowsIds = data_get($state, 'quote_data.selected_rows') ?? [];

        $quote->rowsData()->update(['is_selected' => false]);

        $scope = $reject ? 'whereNotIn' : 'whereIn';

        $quote->rowsData()->{$scope}('imported_rows.id', $selectedRowsIds)
            ->update(['is_selected' => true]);

        /**
         * Recalculate User's Margin Percentage After Select Rows
         */
        $this->calculateMarginPercentage($quote);

        /**
         * Clear Cache Mapping Review Data when Selected Rows was changed
         */
        Cache::forget("mapping-review-data:{$quote->id}");

        return;
    }

    private function calculateMarginPercentage(Quote $quote): Quote
    {
        $quote->list_price = $quote->countTotalPrice();

        if ((float) $quote->list_price === 0.0) {
            $quote->margin_percentage = 0;
            $quote->save();

            return $quote;
        }

        $quote->margin_percentage = round((($quote->list_price - $quote->buy_price) / $quote->list_price) * 100, 2);
        $quote->save();

        return $quote;
    }

    private function attachColumnsToFields(Collection $state, Quote $quote): void
    {
        if (!isset($state['quote_data']['field_column'])) {
            return;
        }

        collect(data_get($state, 'quote_data.field_column'))->each(function ($relation) use ($quote) {
            $templateFieldId = $relation['template_field_id'] ?? null;
            $importableColumnId = $relation['importable_column_id'] ?? null;
            $attributes = collect($relation)->except(['template_field_id', 'importable_column_id'])->all();

            if (!isset($templateFieldId)) {
                return true;
            }

            $templateField = $this->templateField->whereId($templateFieldId)->first();

            if (!isset($importableColumnId) && !(isset($attributes['is_default_enabled']) && $attributes['is_default_enabled'])) {
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
    }

    private function storeQuoteFilesState(Collection $state, Quote $quote): void
    {
        if (!isset($state['quote_data']['files'])) {
            return;
        }

        $stateFiles = collect(data_get($state, 'quote_data.files'));

        $stateFiles->each(function ($fileId) use ($quote) {
            $quoteFile = $this->quoteFile->whereId($fileId)->first();

            if ($quote->quoteFiles()->whereId($fileId)->exists()) {
                return true;
            }

            $quoteFile->quote()->associate($quote)->save();
        });
    }

    private function detachScheduleIfRequested(Collection $state, Quote $quote): void
    {
        if (!isset($state['quote_data']['detach_schedule']) || !$state['quote_data']['detach_schedule']) {
            return;
        }

        $quote->quoteFiles()->paymentSchedules()->delete();
    }
}
