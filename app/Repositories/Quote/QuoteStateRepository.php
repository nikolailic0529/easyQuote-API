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
    Quote\Quote,
    Quote\BaseQuote,
    Quote\Discount as QuoteDiscount,
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteTemplate\TemplateField,
    User
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
use App\Http\Resources\QuoteReviewResource;
use App\Models\Quote\QuoteVersion;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Webpatser\Uuid\Uuid;
use DB, Arr, Str;

class QuoteStateRepository implements QuoteRepositoryInterface
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
        $this->quoteService = $quoteService;
        $this->morphDiscount = $morphDiscount;
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $quote = $request->quote();
            $version = $this->createNewVersionIfNonCreator($quote);

            $state = $request->validatedData();
            $quoteData = $request->validatedQuoteData();

            $version->fill($quoteData);

            $this->draftOrSubmit($state, $quote);

            /**
             * We are attaching passed Files to Quote only when a new version has not been created.
             */
            $quote->wasNotCreatedNewVersion && $this->storeQuoteFilesState($state, $version);

            $this->detachScheduleIfRequested($state, $version);
            $this->attachColumnsToFields($state, $version);
            $this->hideFields($state, $version);
            $this->sortFields($state, $version);
            $this->markRowsAsSelectedOrUnSelected($state, $version);
            $this->setMargin($state, $version);
            $this->setDiscounts($state, $version);

            return ['id' => $version->parent_id];
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

    public function find($quote): Quote
    {
        if (is_string($quote)) {
            return $this->userQuery()->whereId($quote)->withDefaultRelations()->firstOrFail()->withAppends();
        }

        if (!$quote instanceof Quote) {
            throw new \InvalidArgumentException(INV_ARG_QPK_01);
        }

        return $quote->loadDefaultRelations()->withAppends();
    }

    public function findVersion($quote): BaseQuote
    {
        if (is_string($quote)) {
            $quote = $this->find($quote);
        }

        if (!$quote instanceof Quote) {
            throw new \InvalidArgumentException(INV_ARG_QPK_01);
        }

        $quote->usingVersion->loadDefaultRelations();

        return $quote->usingVersion;
    }

    public function create(array $attributes): Quote
    {
        return $this->quote->create($attributes);
    }

    public function make(array $array)
    {
        return $this->quote->make($array);
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
        $quote = $this->findVersion($request->quote_id);

        return cache()->sear($quote->mappingReviewCacheKey, function () use ($quote) {
            return $quote->rowsDataByColumns()->get();
        });
    }

    public function discounts(string $id)
    {
        $quote = $this->findVersion($id);

        $discounts = $this->morphDiscount
            ->whereHasMorph('discountable', $quote->discountsOrder(), function ($query) use ($quote) {
                $query->quoteAcceptable($quote)->activated();
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
        if (!$quote instanceof QuoteVersion) {
            $quote = $this->findVersion($quote);
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

    public function review(string $quoteId)
    {
        $quote = $this->findVersion($quoteId);

        $this->quoteService->prepareQuoteReview($quote);

        return QuoteReviewResource::make($quote->enableReview());
    }

    public function rows(string $id, string $query = ''): Collection
    {
        return $this->findVersion($id)->rowsDataByColumnsGroupable($query)->get();
    }

    public function rowsGroups(string $id): Collection
    {
        $quote = $this->findVersion($id);
        $grouped_rows = $quote->groupedRows()->get();
        $groups_meta = $quote->getGroupDescriptionWithMeta();

        return $grouped_rows->rowsToGroups('group_name', $groups_meta)
            ->exceptEach('group_name')
            ->sortByFields($quote->sort_group_description);
    }

    public function submit(Quote $quote): void
    {
        $this->quoteService->export($quote->usingVersion);
        $submitted_data = QuoteResource::make($quote->usingVersion->disableReview())->resolve();

        $quote->usingVersion->submit($submitted_data);
        $quote->usingVersion->customer->submit();

        $quote->exists && $quote->submit($submitted_data);
    }

    public function draft(Quote $quote): void
    {
        $quote->usingVersion->submitted_data = null;
        $quote->usingVersion->markAsDrafted();
    }

    public function setVersion(string $version_id, $quote): bool
    {
        if (is_string($quote)) {
            $quote = $this->find($quote);
        }

        if (!$quote instanceof Quote) {
            return false;
        }

        $oldVersion = $quote->usingVersion;

        if ($oldVersion->id === $version_id) {
            return false;
        }

        $pass = DB::transaction(function () use ($quote, $version_id) {
            $quote->versions()->where('version_id', '!=', $version_id)->update(['is_using' => false]);
            return $quote->id === $version_id
                || $quote->versions()->where('version_id', $version_id)->update(['is_using' => true]);
        });

        $newVersion = $quote->load('usingVersion')->usingVersion;

        activity()
            ->on($quote)
            ->withAttribute(
                'using_version',
                $newVersion->versionName,
                $oldVersion->versionName
            )
            ->logWhen('updated', $pass);

        return $pass;
    }

    public function findGroupDescription(string $id, string $quote_id): Collection
    {
        $quote = $this->findVersion($quote_id);

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
        $quote = $this->findVersion($quote_id);
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
        $quote = $this->findVersion($quote_id);

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
        $quote = $this->findVersion($quote_id);

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
        $quote = $this->findVersion($quote_id);

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

    public function createNewVersionIfNonCreator(Quote $quote): QuoteVersion
    {
        /**
         * Create replicated version from using version in the following case:
         *
         * 1) Editor id !== $quote->user_id
         * 2) Using version doesn't belong to the current editor.
         *
         * If an editing version (which is using version) belongs to editor, the system shouldn't create a new version and continue modify current version.
         *
         */

        if ($quote->exists && $quote->usingVersion->user_id !== request()->user()->id) {
            $replicatedVersion = $this->replicateVersion($quote, $quote->usingVersion);

            $quote->attachNewVersion($replicatedVersion);
            $quote->load('usingVersion');
        }

        return $quote->usingVersion;
    }

    protected function hideFields(Collection $state, BaseQuote $quote): void
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

    protected function sortFields(Collection $state, BaseQuote $quote): void
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

    protected function freshDiscounts(BaseQuote $quote): void
    {
        $discounts = $quote->load('discounts')->discounts;

        $discounts = $discounts->map(function ($discount) {
            return $discount->toDiscountableArray();
        })->toArray();

        $this->setDiscounts(collect(compact('discounts')), $quote);
    }

    protected function replicateVersion(Quote $parent, QuoteVersion $version, ?User $user = null): BaseQuote
    {
        return DB::transaction(function () use ($parent, $version, $user) {
            $replicatedVersion = $version->replicate(['laravel_through_key']);

            $countUserVersions = $parent->versions()->whereHas('user', function ($query) use ($replicatedVersion) {
                $query->whereId($replicatedVersion->user_id);
            })->count();

            /**
             * We are incrementing a new version on 2 point if parent author equals a new version author as we are not record original version to the pivot table.
             * Incrementing number equals 1 if there is a new version from non-author.
             */
            $countUserVersions += $parent->user_id === $replicatedVersion->user_id ? 1 : 0;

            $replicatedVersion->forceFill([
                'is_version' => true,
                'version_number' => $countUserVersions + 1
            ]);

            if (isset($user)) {
                $replicatedVersion->user()->associate($user);
            }

            $pass = $replicatedVersion->saveOrFail();

            /**
             * Discounts Replication
             */
            $discounts = DB::table('quote_discount')
                ->select(DB::raw("'{$replicatedVersion->id}' `quote_id`"), 'discount_id', 'duration')
                ->where('quote_id', $version->id);
            DB::table('quote_discount')->insertUsing(['quote_id', 'discount_id', 'duration'], $discounts);

            /**
             * Mapping Replication
             */
            $mapping = DB::table('quote_field_column')
                ->select(DB::raw("'{$replicatedVersion->id}' as `quote_id`"), 'template_field_id', 'importable_column_id', 'is_default_enabled')
                ->where('quote_id', $version->id);
            DB::table('quote_field_column')->insertUsing(
                ['quote_id', 'template_field_id', 'importable_column_id', 'is_default_enabled'],
                $mapping
            );

            $quoteFilesToSave = collect();

            $priceList = $version->quoteFiles()->priceLists()->first();
            if (isset($priceList)) {
                $quoteFilesToSave->push($this->quoteFileRepository->replicatePriceList($priceList));
            }

            $schedule = $version->quoteFiles()->paymentSchedules()->with('scheduleData')->first();
            if (isset($schedule)) {
                $replicatedSchedule = $schedule->replicate(['scheduleData']);
                $replicatedSchedule->save();

                if (isset($schedule->scheduleData) && $scheduleData = $schedule->scheduleData->replicate()) {
                    $replicatedSchedule->scheduleData()->save($scheduleData);
                }

                $quoteFilesToSave->push($replicatedSchedule);
            }

            $copied = $pass && $replicatedVersion->quoteFiles()->saveMany($quoteFilesToSave);

            if ($pass) {
                activity()
                    ->on($replicatedVersion)
                    ->withProperties(['old' => $this->quote->logChanges($version), 'attributes' => $this->quote->logChanges($replicatedVersion)])
                    ->log('created_version');
            }

            return $replicatedVersion;
        });
    }

    protected function setMargin(Collection $state, BaseQuote $quote): void
    {
        if ((bool) data_get($state, 'margin.delete', false) === true) {
            $quote->deleteCountryMargin();

            /**
             * Fresh Discounts Margin Percentage.
             */
            $this->freshDiscounts($quote);
            return;
        }

        if (blank($state->get('margin')) || blank($quote->country_id) || blank($quote->vendor_id)) {
            return;
        }

        $countryMargin = $this->margin->firstOrCreate($quote, $state->get('margin'));

        if ($countryMargin->id === $quote->country_margin_id) {
            return;
        }

        $quote->countryMargin()->associate($countryMargin);
        $quote->margin_data = array_merge($countryMargin->only('value', 'method', 'is_fixed'), ['type' => 'By Country']);
        $quote->type = data_get($state, 'margin.quote_type');
        $quote->save();

        /**
         * Fresh Discounts Margin Percentage.
         */
        $this->freshDiscounts($quote);
    }

    protected function setDiscounts(Collection $state, BaseQuote $quote): void
    {
        if ((bool) $state->get('discounts_detach') === true) {
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

        if (blank($discountsState = $state->get('discounts')) || !is_array($discountsState)) {
            return;
        }

        $oldDiscounts = $quote->discounts;

        $discounts = $this->tryDiscounts($discountsState, $quote, false);

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

    protected function draftOrSubmit(Collection $state, Quote $quote): void
    {
        if (!$state->has('save') || !$state->get('save')) {
            $this->draft($quote);
            return;
        }

        if ($state->get('save')) {
            $this->submit($quote);
        }
    }

    protected function markRowsAsSelectedOrUnSelected(Collection $state, BaseQuote $quote, bool $reject = false): void
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

    protected function attachColumnsToFields(Collection $state, BaseQuote $quote): void
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

    protected function storeQuoteFilesState(Collection $state, BaseQuote $quote): void
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

    protected function detachScheduleIfRequested(Collection $state, BaseQuote $quote): void
    {
        if (!data_get($state, 'quote_data.detach_schedule', false) || !$quote->paymentSchedule()->exists()) {
            return;
        }

        $quote->quoteFiles()->paymentSchedules()->delete();
    }
}
