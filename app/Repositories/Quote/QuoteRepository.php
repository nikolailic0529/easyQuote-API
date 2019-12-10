<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\{
    Quote\QuoteRepositoryInterface,
    Quote\Margin\MarginRepositoryInterface as MarginRepository,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository,
    QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectSeparatorRepository
};
use App\Contracts\Services\QuoteServiceInterface as QuoteService;
use App\Models\{
    Company,
    Quote\Quote,
    Quote\Discount as QuoteDiscount,
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteTemplate\TemplateField
};
use App\Http\Requests\{
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Http\Requests\Quote\TryDiscountsRequest;
use App\Http\Resources\QuoteResource;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Webpatser\Uuid\Uuid;
use DB, Arr, Str, Setting;

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

    protected $morphDiscount;

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
        DataSelectSeparatorRepository $dataSelectSeparator,
        QuoteDiscount $morphDiscount
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
        $this->morphDiscount = $morphDiscount;
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $quote = $request->quote();
            $state = $request->validatedData();
            $quoteData = $request->validatedQuoteData();

            filled($quoteData) && $quote->fill($quoteData);

            $this->draftOrSubmit($state, $quote);
            $this->storeQuoteFilesState($state, $quote);
            $this->detachScheduleIfRequested($state, $quote);
            $this->attachColumnsToFields($state, $quote);
            $this->hideFields($state, $quote);
            $this->sortFields($state, $quote);
            $this->markRowsAsSelectedOrUnSelected($state, $quote);
            $this->setMargin($quote, $request->margin);
            $this->setDiscounts($quote, $request->discounts, $request->discounts_detach);

            return $quote->only('id');
        });
    }

    public function userQuery(): Builder
    {
        return $this->quote->query()->currentUserWhen(request()->user()->cant('view_quotes'));
    }

    public function findOrNew(string $id)
    {
        return $this->userQuery()->whereId($id)->firstOrNew();
    }

    public function find(string $id)
    {
        $quote = $this->userQuery()
            ->whereId($id)
            ->with([
                'quoteFiles' => function ($query) {
                    return $query->isNotHandledSchedule();
                },
                'quoteTemplate.templateFields.templateFieldType',
                'countryMargin',
                'discounts',
                'customer',
                'country',
                'vendor'
            ])
            ->firstOrFail()
            ->append(
                'list_price',
                'hidden_fields',
                'sort_fields',
                'field_column',
                'rows_data',
                'margin_percentage_without_country_margin',
                'margin_percentage_without_discounts',
                'user_margin_percentage'
            );

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
        $companies = $this->company->with('vendors.countries')->ordered()->get();

        $data_select_separators = $this->dataSelectSeparator->all();

        $supported_file_types = Setting::get('supported_file_types_ui');

        return compact('supported_file_types', 'data_select_separators', 'companies');
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

        return cache()->sear($quote->mappingReviewCacheKey, function () use ($quote) {
            return $quote->rowsDataByColumns()->get();
        });
    }

    public function hideFields(Collection $state, Quote $quote): void
    {
        if (is_null($hidden = data_get($state, 'quote_data.hidden_fields'))) {
            return;
        }

        $quote->fieldsColumns()->whereHas('templateField', function ($query) use ($hidden) {
            $query->whereIn('name', $hidden);
        })
            ->update(['is_preview_visible' => false]);

        $quote->fieldsColumns()->whereHas('templateField', function ($query) use ($hidden) {
            $query->whereNotIn('name', $hidden);
        })
            ->update(['is_preview_visible' => true]);

        $quote->forgetCachedComputableRows();
    }

    public function sortFields(Collection $state, Quote $quote): void
    {
        if (is_null($sort = data_get($state, 'quote_data.sort_fields'))) {
            return;
        }

        $quote->fieldsColumns()->update(['sort' => null]);

        if (blank($sort)) {
            return;
        }

        collect($sort)->groupBy('direction')->each(function ($sort, $direction) use ($quote) {
            $quote->fieldsColumns()->whereHas('templateField', function ($query) use ($sort) {
                $query->whereIn('name', Arr::pluck($sort, 'name'));
            })
                ->update(['sort' => $direction]);
        });

        $quote->forgetCachedComputableRows();
        $quote->forgetCachedMappingReview();
    }

    public function setMargin(Quote $quote, ?array $attributes): void
    {
        if (blank($attributes) || blank($quote->country_id) || blank($quote->vendor_id)) {
            return;
        }

        if (isset($attributes['delete']) && $attributes['delete']) {
            $quote->deleteCountryMargin();

            /**
             * Fresh Discounts Margin Percentage.
             */
            $this->freshDiscounts($quote);
            return;
        }

        $countryMargin = $this->margin->firstOrCreate($quote, $attributes);

        if ($countryMargin->id === $quote->country_margin_id) {
            return;
        }

        $quote->countryMargin()->associate($countryMargin);
        $quote->margin_data = array_merge($countryMargin->only('value', 'method', 'is_fixed'), ['type' => 'By Country']);
        $quote->type = $attributes['quote_type'];

        $quote->save();

        /**
         * Fresh Discounts Margin Percentage.
         */
        $this->freshDiscounts($quote);
    }

    public function setDiscounts(Quote $quote, $attributes, $detach): void
    {
        if ((bool) $detach === true) {
            $quote->resetCustomDiscount();

            if ($quote->discounts->isEmpty()) {
                return;
            }

            $oldDiscounts = $quote->discounts;
            $quote->discounts()->detach();

            activity()
                ->on($quote)
                ->withAttribute('discounts', null, $oldDiscounts->toString('discountable.name'))
                ->log('updated');

            return;
        }

        if (!isset($attributes) || !is_array($attributes) || empty($attributes)) {
            return;
        }

        $oldDiscounts = $quote->discounts;

        $discounts = $this->tryDiscounts($attributes, $quote, false);

        $attachableDiscounts = $discounts->map(function ($discount) {
            return $discount->toAttachableArray();
        })->collapse()->toArray();

        $quote->discounts()->sync($attachableDiscounts);

        $newDiscounts = $quote->load('discounts')->discounts;

        if ($quote->custom_discount > 0) {
            $quote->resetCustomDiscount();
        }

        $diff = $newDiscounts->pluck('discountable.id')->udiff($oldDiscounts->pluck('discountable.id'))->isNotEmpty();

        activity()
            ->on($quote)
            ->withAttribute('discounts', $newDiscounts->toString('discountable.name'), $oldDiscounts->toString('discountable.name'))
            ->logWhen('updated', $diff);
    }

    public function discounts(string $id)
    {
        $quote = $this->find($id);

        $discounts = $this->morphDiscount->whereHasMorph('discountable', $quote->discountsOrder(), function ($query) use ($quote) {
            $query->quoteAcceptable($quote);
        })->get()->pluck('discountable');

        $expectingDiscounts = ['multi_year' => [], 'pre_pay' => [], 'promotions' => [], 'snd' => []];

        return $discounts->groupBy(function ($discount) {
            switch ($discount->discount_type) {
                case 'PromotionalDiscount':
                    $type = 'PromotionsDiscount';
                    break;
                case 'SND':
                    $type = 'sndDiscount';
                    break;
                default:
                    $type = $discount->discount_type;
                    break;
            }
            return Str::snake(Str::before($type, 'Discount'));
        })->union($expectingDiscounts);
    }

    public function tryDiscounts($attributes, $quote, bool $group = true): Collection
    {
        if (!$quote instanceof Quote) {
            $quote = $this->find($quote);
        }

        if ($attributes instanceof TryDiscountsRequest) {
            $attributes = $attributes->validated();
        }

        $durationsSelect = collect($attributes)->transform(function ($discount) {
            return sprintf("when '%s' then %s", $discount['id'], $discount['duration'] ?? 'null');
        })->prepend('case `discountable_id`')->push('else null end')->implode(" ");

        $providedDiscounts = $this->morphDiscount->with('discountable')->whereIn('discountable_id', Arr::pluck($attributes, 'id'))
            ->select(['*', DB::raw("({$durationsSelect}) as `duration`")])
            ->orderByRaw("field(`discounts`.`discountable_type`, {$quote->discountsOrderToString()})", 'desc')
            ->get();

        /**
         * We are reassigning the Quote Discounts Relation for Calculation new Margin Percentage after provided Discounts applying.
         */
        $quote->discounts = $providedDiscounts;

        $this->quoteService->assignComputableRows($quote);

        /**
         * Possible Interactions with Margins and Discounts
         */
        $this->quoteService->interactWithModels($quote);

        $interactedDiscounts = $quote->discounts;
        $quote->unsetRelation('discounts');
        $quote->load('discounts');

        if (!$group) {
            return $interactedDiscounts;
        }

        return $interactedDiscounts->groupBy(function ($discount) {
            $type = $discount->discount_type === 'PromotionalDiscount' ? 'PromotionsDiscount' : $discount->discount_type;
            return Str::snake(Str::before($type, 'Discount'));
        });
    }

    public function freshDiscounts(Quote $quote): void
    {
        $discounts = $quote->load('discounts')->discounts;

        $discountable = $discounts->map(function ($discount) {
            return $discount->toDiscountableArray();
        })->toArray();

        $this->setDiscounts($quote, $discountable, false);
    }

    public function review(string $quoteId)
    {
        $quote = $this->find($quoteId);

        $this->quoteService->prepareQuoteReview($quote);

        return data_get((new QuoteResource($quote))->resolve(), 'quote_data');
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

        return $grouped_rows->rowsToGroups('group_name', $groups_meta)
            ->exceptEach('group_name')
            ->sortByFields($quote->sort_group_description);
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
        abort_if($group_key === false, 404, 'The Group Description is not found.');

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
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $data = collect($request->validated());
        $group = $data->only(['name', 'search_text'])->prepend(Uuid::generate(4)->string, 'id');

        $rows = $data->get('rows', []);

        $quote->rowsData()->whereIn('imported_rows.id', $rows)
            ->update(['group_name' => $group->get('name')]);

        $quote->group_description = collect($quote->group_description)->push($group)->values();

        $quote->save();

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->log('updated');

        $quote->forgetCachedComputableRows();

        return $group;
    }

    public function updateGroupDescription(UpdateGroupDescriptionRequest $request, string $id, string $quote_id): bool
    {
        $quote = $this->find($quote_id);

        $group_description = collect($quote->group_description);
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $data = collect($request->validated());
        $group = $data->only(['name', 'search_text'])->toArray();
        $rows = $data->get('rows', []);

        $group_key = $quote->findGroupDescription($id);
        abort_if($group_key === false, 404, 'The Group Description is not found.');

        $updatableGroup = $group_description->get($group_key);

        $quote->rowsData()->whereGroupName($updatableGroup['name'])
            ->update(['group_name' => null]);

        $quote->rowsData()->whereIn('imported_rows.id', $rows)
            ->update(['group_name' => $group['name']]);

        $updatedGroup = array_merge($updatableGroup, $group);

        $quote->group_description = collect($quote->group_description)->put($group_key, $updatedGroup)->values();

        $saved = $quote->save();

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->log('updated');

        $quote->forgetCachedComputableRows();

        return $saved;
    }

    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, string $quote_id): bool
    {
        $quote = $this->find($quote_id);

        $group_description = collect($quote->group_description);
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $from_group_key = $quote->findGroupDescription($request->from_group_id);
        $to_group_key = $quote->findGroupDescription($request->to_group_id);

        abort_if(($from_group_key === false || $to_group_key === false), 404, 'The From or To Group Description is not found.');

        $from_group = $group_description->get($from_group_key);
        $to_group = $group_description->get($to_group_key);

        $updated = $quote->rowsData()->whereGroupName($from_group['name'])
            ->whereIn('imported_rows.id', $request->rows)
            ->update(['group_name' => $to_group['name']]);

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->log('updated');

        $quote->forgetCachedComputableRows();

        return $updated;
    }

    public function deleteGroupDescription(string $id, string $quote_id): bool
    {
        $quote = $this->find($quote_id);

        $group_description = collect($quote->group_description);
        $old_group_description_with_meta = $quote->group_description_with_meta;

        $group_key = $quote->findGroupDescription($id);

        abort_if($group_key === false, 404, 'The Group Description is not found.');

        $removableGroup = $group_description->get($group_key);

        $quote->rowsData()->whereGroupName($removableGroup['name'])
            ->update(['group_name' => null]);

        $group_description->forget($group_key);

        $quote->group_description = $group_description->isEmpty() ? null : $group_description->values();

        if (blank($quote->group_description)) {
            $quote->sort_group_description = null;
        }

        $saved = $quote->save();

        activity()
            ->on($quote)
            ->withAttribute(
                'group_description',
                $quote->group_description_with_meta->toString('name', 'total_count'),
                $old_group_description_with_meta->toString('name', 'total_count')
            )
            ->log('updated');

        $quote->forgetCachedComputableRows();

        return $saved;
    }

    private function markRowsAsSelectedOrUnSelected(Collection $state, Quote $quote, bool $reject = false): void
    {
        if (blank($selectedRowsIds = data_get($state, 'quote_data.selected_rows', [])) && blank(data_get($state, 'quote_data.selected_rows_is_rejected'))) {
            return;
        };

        if (data_get($state, 'quote_data.selected_rows_is_rejected', false)) {
            $reject = true;
        };

        $updatableScope = $reject ? 'whereNotIn' : 'whereIn';

        $oldRowsIds = $quote->rowsData()->selected()->pluck('imported_rows.id');

        $quote->rowsData()->update(['is_selected' => false]);

        $quote->rowsData()->{$updatableScope}('imported_rows.id', $selectedRowsIds)
            ->update(['is_selected' => true]);

        $newRowsIds = $quote->rowsData()->selected()->pluck('imported_rows.id');

        if ($newRowsIds->udiff($oldRowsIds)->isEmpty()) {
            return;
        }

        activity()
            ->on($quote)
            ->withAttribute('selected_rows', $newRowsIds->count(), $oldRowsIds->count())
            ->log('updated');

        /**
         * Fresh Discounts Margin Percentage.
         */
        $this->freshDiscounts($quote);

        /**
         * Clear Cache Mapping Review Data when Selected Rows were changed.
         */
        $quote->forgetCachedMappingReview();

        /**
         * Clear Cache Computable Rows when Selected Rows were changed.
         */
        $quote->forgetCachedComputableRows();
    }

    private function attachColumnsToFields(Collection $state, Quote $quote): void
    {
        if (blank($fieldsColumns = collect(data_get($state, 'quote_data.field_column')))) {
            return;
        }

        $oldFieldsColumns = $quote->fieldsColumns;

        $syncData = $fieldsColumns->filter(function ($fieldColumn) {
            return (filled(data_get($fieldColumn, 'importable_column_id')) || data_get($fieldColumn, 'is_default_enabled', false));
        })->keyBy('template_field_id')->exceptEach('template_field_id')->toArray();

        $quote->templateFields()->sync($syncData);

        $newFieldsColumns = $quote->load('fieldsColumns')->fieldsColumns;

        $hasChanges = $newFieldsColumns->contains(function ($newFieldColumn) use ($oldFieldsColumns) {
            $oldFieldColumn = $oldFieldsColumns->firstWhere('template_field_id', $newFieldColumn['template_field_id']);

            if (blank($oldFieldColumn)) {
                return true;
            }

            return filled(Arr::isDifferentAssoc($newFieldColumn->getAttributes(), $oldFieldColumn->getAttributes()));
        });

        if (!$hasChanges) {
            return;
        }

        /**
         * Clear Cache Mapping Review Data when Mapping was changed.
         */
        $quote->forgetCachedMappingReview();

        /**
         * Clear Cache Computable Rows when Mapping was changed.
         */
        $quote->forgetCachedComputableRows();
    }

    private function storeQuoteFilesState(Collection $state, Quote $quote): void
    {
        if (blank($stateFiles = collect(data_get($state, 'quote_data.files')))) {
            return;
        }

        $existingQuoteFiles = $quote->quoteFiles->pluck('id');
        $stateFiles = $stateFiles->diff($existingQuoteFiles);

        if (blank($stateFiles)) {
            return;
        }

        $oldQuoteFiles = $quote->quoteFiles;

        $this->quoteFile->whereIn('id', $stateFiles)->update(['quote_id' => $quote->id]);

        $newQuoteFiles = $quote->load('quoteFiles')->quoteFiles;

        activity()
            ->performedOn($quote)
            ->withAttribute('quote_files', $newQuoteFiles->toString('original_file_name'), $oldQuoteFiles->toString('original_file_name'))
            ->log('updated');
    }

    private function detachScheduleIfRequested(Collection $state, Quote $quote): void
    {
        if (!data_get($state, 'quote_data.detach_schedule', false) || !$quote->paymentSchedule()->exists()) {
            return;
        }

        $quote->quoteFiles()->paymentSchedules()->delete();
    }
}
