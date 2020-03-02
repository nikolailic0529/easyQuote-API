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
    Quote\StoreQuoteStateRequest,
    MappingReviewRequest
};
use App\Http\Requests\Quote\TryDiscountsRequest;
use App\Http\Resources\QuoteReviewResource;
use App\Jobs\RetrievePriceAttributes;
use App\Models\Quote\QuoteVersion;
use App\Repositories\Concerns\{
    ManagesGroupDescription,
    ManagesSchemalessAttributes,
    ResolvesImplicitModel,
    ResolvesQuoteVersion
};
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use DB, Arr, Str;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;

class QuoteStateRepository implements QuoteRepositoryInterface
{
    use ResolvesImplicitModel, ResolvesQuoteVersion, ManagesGroupDescription, ManagesSchemalessAttributes;

    protected Quote $quote;

    protected QuoteService $quoteService;

    protected QuoteFile $quoteFile;

    protected QuoteFileRepository $quoteFileRepository;

    protected MarginRepository $margin;

    protected QuoteTemplateRepository $quoteTemplate;

    protected TemplateField $templateField;

    protected ImportableColumn $importableColumn;

    protected QuoteDiscount $morphDiscount;

    public function __construct(
        Quote $quote,
        QuoteService $quoteService,
        QuoteFile $quoteFile,
        QuoteTemplateRepository $quoteTemplate,
        QuoteFileRepository $quoteFileRepository,
        MarginRepository $margin,
        TemplateField $templateField,
        ImportableColumn $importableColumn,
        QuoteDiscount $morphDiscount
    ) {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->quoteFileRepository = $quoteFileRepository;
        $this->margin = $margin;
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

            /**
             * We are swapping $version instance to Quote instance if the Version is Parent.
             * It needs to perform actions with it as with Quote instance.
             */
            $version = $this->resolveQuoteVersion($quote, $version);

            $state = $request->validatedData();
            $quoteData = $request->validatedQuoteData();

            $version->fill($quoteData)->markAsDrafted();

            $this->draftOrSubmit($state, $quote);

            /**
             * We are attaching passed Files to Quote only when a new version has not been created.
             */
            $quote->wasNotCreatedNewVersion && $this->storeQuoteFilesState($state, $version);

            $this->detachScheduleIfRequested($state, $version);
            $this->attachColumnsToFields($state, $version);
            $this->hideFields($state, $version);
            $this->sortFields($state, $version);

            /**
             * We are selecting imported rows only when a new version has not been created.
             * Rows will be selected when replicating the version.
             */
            $quote->wasNotCreatedNewVersion && $this->markRowsAsSelectedOrUnSelected($state, $version);

            $this->setMargin($state, $version);
            $this->setDiscounts($state, $version);

            /**
             * We are always returning original Quote Id regardless of the versions.
             */
            return ['id' => $version->parent_id];
        }, 3);
    }

    public function userQuery(): Builder
    {
        return tap($this->quote->query(), function (Builder $query) {
            !app()->runningUnitTests() && $query->currentUserWhen(
                request()->user()->cant('view_quotes')
            );
        });
    }

    public function findOrNew(string $id)
    {
        return $this->userQuery()->whereId($id)->firstOrNew();
    }

    public function find(string $id): Quote
    {
        return $this->quote->whereId($id)->firstOrFail();
    }

    public function findVersion($quote): BaseQuote
    {
        if ($quote instanceof QuoteVersion) {
            return $quote;
        }

        return $this->resolveModel($quote)->usingVersion;
    }

    public function create(array $attributes): Quote
    {
        return $this->quote->create($attributes);
    }

    public function make(array $array)
    {
        return $this->quote->make($array);
    }

    public function retrieveRows(BaseQuote $quote, $criteria = [])
    {
        $subQuery = $this->mappedRowsQuery($quote);
        $query = DB::query()->fromSub($subQuery, 'mapped_rows');

        $mapping = $quote->fieldsColumns;
        $columns = $mapping->pluck('templateField.name');

        $query->addSelect('id', 'is_selected', 'group_name', ...$columns);

        /** Sorting by columns. */
        $mapping->each(fn ($map) =>
            /** Except the price column to be able calculate price. */
            $query->when($map->templateField->name !== 'price', fn (QueryBuilder $query) => $query->addSelect($map->templateField->name))
                ->when(filled($map->sort), fn (QueryBuilder $query) => $query->orderBy($map->templateField->name, $map->sort))
        );

        /** An optional criteria for query. */
        if ($criteria instanceof Closure) {
            call_user_func($criteria, $query);
        }

        if (is_array($criteria)) {
            $query->where($criteria);
        }

        return $query->get();
    }

    public function calculateListPrice(BaseQuote $quote): float
    {
        return $this->mappedRowsQuery($quote)->sum('price');
    }

    public function calculateTotalPrice(BaseQuote $quote): float
    {
        return $this->mappedRowsQuery($quote)
            ->when($quote->groupsReady(), fn (QueryBuilder $query) => $query->whereNotNull('group_name'), fn (QueryBuilder $query) => $query->whereIsSelected(true))
            ->sum('price');
    }

    public function discounts(string $id)
    {
        $quote = $this->findVersion($id);

        $discounts = $this->morphDiscount
            ->whereHasMorph('discountable', $quote->discountsOrder(), fn ($query) => $query->quoteAcceptable($quote)->activated())
            ->get()->pluck('discountable');

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

        $this->setComputableRows($quote);

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

    public function review(string $quote)
    {
        $quote = $this->findVersion($quote);

        $this->quoteService->prepareQuoteReview($quote);

        return QuoteReviewResource::make($quote->enableReview());
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
            ->queueWhen('updated', $pass);

        return $pass;
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
         */
        if ($quote->exists && $quote->usingVersion->user_id !== auth()->id()) {
            $replicatedVersion = $this->replicateVersion($quote, $quote->usingVersion);

            $quote->attachNewVersion($replicatedVersion);
            $quote->load('usingVersion');
        }

        return $quote->usingVersion;
    }

    public function model(): string
    {
        return Quote::class;
    }

    public function replicateDiscounts(string $sourceId, string $targetId): void
    {
        DB::table('quote_discount')->insertUsing(
            ['quote_id', 'discount_id', 'duration'],
            DB::table('quote_discount')->select(
                DB::raw("'{$targetId}' as quote_id"),
                'discount_id',
                'duration'
            )
                ->where('quote_id', $sourceId)
        );
    }

    public function replicateMapping(string $sourceId, string $targetId): void
    {
        DB::table('quote_field_column')->insertUsing(
            ['quote_id', 'template_field_id', 'importable_column_id', 'is_default_enabled'],
            DB::table('quote_field_column')->select(
                DB::raw("'{$targetId}' as quote_id"),
                'template_field_id',
                'importable_column_id',
                'is_default_enabled'
            )
                ->where('quote_id', $sourceId)
        );
    }

    protected function mappedRowsQuery(BaseQuote $quote): QueryBuilder
    {
        $query = DB::table('imported_rows')
            ->join('quote_files', 'quote_files.id', '=', 'imported_rows.quote_file_id')
            ->join('customers', fn (JoinClause $join) => $join->where('customers.id', $quote->customer_id))
            ->whereNull('quote_files.deleted_at')
            ->whereNull('imported_rows.deleted_at')
            ->whereNotNull('imported_rows.columns_data')
            ->where('quote_files.quote_id', $quote->id)
            ->where('quote_files.file_type', QFT_PL)
            ->whereColumn('imported_rows.page', '>=', 'quote_files.imported_page');

        $mapping = $quote->fieldsColumns;

        $exchangeRate = $quote->convertExchangeRate(1);

        $customerAttributesMap = ['date_from' => 'customer_support_start', 'date_to' => 'customer_support_end'];

        $query->select('imported_rows.id', 'imported_rows.is_selected', 'imported_rows.group_name', 'imported_rows.columns_data', 'customers.support_start as customer_support_start', 'customers.support_end as customer_support_end');

        $mapping->each(function ($map) use ($query, $customerAttributesMap) {
            if (!$map->is_default_enabled) {
                $this->unpivotJsonColumn($query, 'columns_data', 'importable_column_id', $map->importable_column_id, 'value', $map->templateField->name);
                return true;
            }

            switch ($map->templateField->name) {
                case 'date_from':
                case 'date_to':
                    $defaultCustomerDate = optional($customerAttributesMap)[$map->templateField->name];
                    $query->selectRaw("date_format(`{$defaultCustomerDate}`, '%d/%m/%Y') as {$map->templateField->name}");
                    break;
                case 'qty':
                    $query->selectRaw("1 as {$map->templateField->name}");
                    break;
            }
        });

        $query = DB::query()->fromSub($query, 'imported_rows')->addSelect('id', 'is_selected', 'columns_data', 'group_name');

        $mapping->each(function ($map) use ($query, $customerAttributesMap, $exchangeRate) {
            if ($map->is_default_enabled) {
                $query->addSelect($map->templateField->name);
                return true;
            }

            switch ($map->templateField->name) {
                case 'price':
                    $query->selectRaw('CAST(ExtractDecimal(`price`) * ? AS DECIMAL(15,2)) as `price`', [$exchangeRate]);
                    break;
                case 'date_from':
                case 'date_to':
                    $defaultCustomerDate = optional($customerAttributesMap)[$map->templateField->name];
                    $this->parseColumnDate($query, $map->templateField->name, "`{$defaultCustomerDate}`");
                    break;
                case 'qty':
                    $query->selectRaw("GREATEST(CAST(`qty` AS UNSIGNED), 1) AS `qty`");
                    break;
                default:
                    $query->addSelect($map->templateField->name);
                    break;
            }
        });

        /** Select default values when the related mapping doesn't exist. */
        $defaults = collect(['price' => 0, 'date_from' => '`customer_support_start`', 'date_to' => '`customer_support_end`']);

        $defaults->each(fn ($value, $column) =>
            $query->unless($mapping->contains('templateField.name', $column), fn (QueryBuilder $query) => $query->selectRaw("{$value} AS `{$column}`"))
        );

        $query = DB::query()->fromSub($query, 'rows');

        $columns = $mapping->pluck('templateField.name')->flip()->except('price')->flip();
        $query->addSelect('id', 'is_selected', 'columns_data', 'group_name', ...$columns);

        /** Calculating price based on date_from & date_to when related option is selected. */
        $query->when($quote->calculate_list_price, fn (QueryBuilder $query) => $query->selectRaw("(`price` / 30 * GREATEST(DATEDIFF(STR_TO_DATE(`date_to`, '%d/%m/%Y'), STR_TO_DATE(`date_from`, '%d/%m/%Y')), 0)) as `price`"))
            ->unless($quote->calculate_list_price, fn (QueryBuilder $query) => $query->addSelect('price'));


        return $query;
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

            $versionNumber = $this->countVersionNumber($parent, $replicatedVersion);

            $replicatedVersion->forceFill([
                'is_version' => true,
                'version_number' => $versionNumber
            ]);

            if (isset($user)) {
                $replicatedVersion->user()->associate($user);
            }

            $pass = $replicatedVersion->save();

            /** Discounts Replication. */
            $this->replicateDiscounts($version->id, $replicatedVersion->id);

            /** Mapping Replication. */
            $this->replicateMapping($version->id, $replicatedVersion->id);

            $version->quoteFiles()->get()->each(function ($quoteFile) use ($replicatedVersion) {
                switch ($quoteFile->file_type) {
                    case QFT_PL:
                        $this->quoteFileRepository->replicatePriceList($quoteFile, $replicatedVersion->id);
                        break;
                    case QFT_PS:
                        tap($quoteFile->replicate(), function ($schedule) use ($replicatedVersion, $quoteFile) {
                            $schedule->quote()->associate($replicatedVersion);
                            $schedule->save();
                            $schedule->scheduleData()->save($quoteFile->scheduleData->replicate());
                        });
                        break;
                }
            });

            if ($pass) {
                activity()
                    ->on($replicatedVersion)
                    ->withProperties(['old' => $this->quote->logChanges($version), 'attributes' => $this->quote->logChanges($replicatedVersion)])
                    ->queue('created_version');
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
                ->queue('updated');

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
            ->queueWhen('updated', $diff);
    }

    protected function draftOrSubmit(Collection $state, Quote $quote): void
    {
        if ($state->get('save', false) == false) {
            $quote->save();
            return;
        }

        if ($state->get('save')) {
            $quote->disableLogging()->tap()->submit()->enableLogging();
        }
    }

    protected function markRowsAsSelectedOrUnSelected(Collection $state, BaseQuote $quote, bool $reject = false): void
    {
        if (blank($selectedRowsIds = data_get($state, 'quote_data.selected_rows', [])) && blank(data_get($state, 'quote_data.selected_rows_is_rejected'))) {
            return;
        }

        if (data_get($state, 'quote_data.selected_rows_is_rejected', false)) {
            $reject = true;
        }

        $updatableScope = $reject ? 'whereNotIn' : 'whereIn';

        $oldRowsIds = $quote->rowsData()->selected()->pluck('imported_rows.id');

        DB::transaction(function () use ($quote, $updatableScope, $selectedRowsIds) {
            $quote->rowsData()->update(['is_selected' => false]);

            $quote->rowsData()->{$updatableScope}('imported_rows.id', $selectedRowsIds)
                ->update(['is_selected' => true]);
        }, 3);

        $newRowsIds = $quote->rowsData()->selected()->pluck('imported_rows.id');

        if ($newRowsIds->udiff($oldRowsIds)->isEmpty()) {
            return;
        }

        activity()
            ->on($quote)
            ->withAttribute('selected_rows', $newRowsIds->count(), $oldRowsIds->count())
            ->queue('updated');

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

        dispatch(new RetrievePriceAttributes($quote));

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
            ->queue('updated');
    }

    protected function detachScheduleIfRequested(Collection $state, BaseQuote $quote): void
    {
        if (!data_get($state, 'quote_data.detach_schedule', false) || !$quote->paymentSchedule()->exists()) {
            return;
        }

        $quote->quoteFiles()->paymentSchedules()->delete();
    }

    protected function setComputableRows(BaseQuote $quote): void
    {
        $quote->computableRows = $this->retrieveRows($quote, ['is_selected' => true]);
    }

    private function countVersionNumber(Quote $quote, QuoteVersion $version): int
    {
        $count = $quote->versions()->where('user_id', $version->user_id)->count();

        /**
         * We are incrementing a new version on 2 point if parent author equals a new version author as we are not record original version to the pivot table.
         * Incrementing number equals 1 if there is a new version from non-author.
         */
        if ($quote->user_id === $version->user_id) {
            $count += 1;
        }

        return ++$count;
    }
}
