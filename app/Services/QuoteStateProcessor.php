<?php

namespace App\Services;

use App\Collections\MappedRows;
use JetBrains\PhpStorm\ArrayShape;
use App\Contracts\{Services\ManagesDocumentProcessors, Services\QuoteState, Services\QuoteView as QuoteService};
use App\Contracts\Repositories\{QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository};
use App\DTO\RowsGroup;
use App\Enum\Lock;
use App\Events\RescueQuote\RescueQuoteCreated;
use App\Events\RescueQuote\RescueQuoteSubmitted;
use App\Events\RescueQuote\RescueQuoteUnravelled;
use App\Events\RescueQuote\RescueQuoteUpdated;
use App\Http\Requests\{Quote\StoreQuoteStateRequest,};
use App\Http\Requests\Quote\TryDiscountsRequest;
use App\Http\Resources\QuoteReviewResource;
use App\Jobs\MigrateQuoteAssets;
use App\Jobs\RetrievePriceAttributes;
use App\Models\{Quote\BaseQuote,
    Quote\Discount as MorphDiscount,
    Quote\DistributionFieldColumn,
    Quote\FieldColumn,
    Quote\Margin\CountryMargin,
    Quote\Quote,
    Quote\QuoteVersionFieldColumn,
    QuoteFile\ImportedRow,
    QuoteFile\QuoteFile,
    User};
use App\Models\Quote\QuoteVersion;
use App\Queries\QuoteQueries;
use App\Repositories\Concerns\{ManagesGroupDescription,
    ManagesSchemalessAttributes,
    ResolvesImplicitModel,
    ResolvesTargetModel};
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Cache\Lock as LockContract;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\{Arr, Facades\DB, Str};
use Illuminate\Support\Collection;
use Throwable;

class QuoteStateProcessor implements QuoteState
{
    use ResolvesImplicitModel, ResolvesTargetModel, ManagesGroupDescription, ManagesSchemalessAttributes;

    public function __construct(
        protected ConnectionInterface       $connection,
        protected LockProvider              $lockProvider,
        protected EventDispatcher           $eventDispatcher,
        protected BusDispatcher             $busDispatcher,
        protected QuoteService              $quoteService,
        protected QuoteFileRepository       $quoteFileRepository,
        protected QuoteQueries              $quoteQueries,
        protected ManagesDocumentProcessors $documentProcessor,
    )
    {
    }

    /**
     * @param StoreQuoteStateRequest $request
     * @return array
     * @throws Throwable
     */
    #[ArrayShape(['id' => "string"])]
    public function storeState(StoreQuoteStateRequest $request): array
    {
        $quote = $request->getQuote();

        $newQuote = false === $quote->exists;

        /** @var LockContract $lock */
        $lock = value(function () use ($quote): LockContract {
            if (false === $quote->exists) {
                return $this->lockProvider->lock(
                    Lock::CREATE_QUOTE,
                    10
                );
            }

            return $this->lockProvider->lock(
                Lock::UPDATE_QUOTE($quote->getKey()),
                10
            );
        });

        $lock->block(30);

        $this->connection->beginTransaction();

        try {
            $version = $this->createNewVersionIfNonCreator($quote);

            /**
             * We are swapping $version instance to Quote instance if the Version is Parent.
             * It needs to perform actions with it as with Quote instance.
             */
            /** @var BaseQuote $version */
            $version = $this->resolveTargetModel($quote, $version);

            $state = $request->validatedData();
            $quoteData = $request->validatedQuoteData();

            $version->fill($quoteData)->save();

            $quoteWasRecentlySubmitted = $this->draftOrSubmit($state, $quote);

            /**
             * We are attaching passed Files to Quote only when a new version has not been created.
             */
            if (false === $quote->wasCreatedNewVersion) {
                $this->storeQuoteFilesState($state, $version);
            }

            $this->detachScheduleIfRequested($state, $version);
            $this->attachColumnsToFields($state, $version);
            $this->hideFields($state, $version);
            $this->sortFields($state, $version);

            /**
             * We are selecting imported rows only when a new version has not been created.
             * Rows will be selected when replicating the version.
             */
            if (false === $quote->wasCreatedNewVersion) {
                $this->markRowsAsSelectedOrUnSelected($state, $version);
            }

            $this->setMargin($state, $version);
            $this->setDiscounts($state, $version);

            $this->connection->commit();

            if ($newQuote) {
                $this->eventDispatcher->dispatch(new RescueQuoteCreated($quote));
            } else {
                $this->eventDispatcher->dispatch(new RescueQuoteUpdated($quote));
            }

            if ($quoteWasRecentlySubmitted) {
                $this->eventDispatcher->dispatch(new RescueQuoteSubmitted($quote));
            }

            /**
             * We are always returning original Quote Id regardless of the versions.
             */
            return ['id' => $quote->getKey()];
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    #[ArrayShape(['status' => "string"])]
    public function processQuoteFileImport(Quote     $quote,
                                           QuoteFile $quoteFile,
                                           ?int      $importablePageNumber = null,
                                           ?string   $dataSeparatorReference = null): mixed
    {
        $this->ensureQuoteFileProcessed(
            quote: $quote,
            quoteFile: $quoteFile,
            importablePageNumber: $importablePageNumber
        );

        $quoteFile->throwExceptionIfExists();

        if ($quoteFile->isPrice() && false === $quoteFile->mappingWasGuessed()) {
            $this->guessQuoteMapping($quote);

            $this->busDispatcher->dispatch(
                new RetrievePriceAttributes($quote->activeVersionOrCurrent)
            );
        }

        return [
            'status' => 'completed',
        ];
    }

    protected function ensureQuoteFileProcessed(Quote     $quote,
                                                QuoteFile $quoteFile,
                                                ?int      $importablePageNumber = null,
                                                ?string   $dataSeparatorReference = null): bool
    {
        $shouldProcess = value(function () use ($quoteFile): bool {

            if ($quoteFile->hasException()) {
                return false;
            }

            return true;

        });

        if (false === $shouldProcess) {
            return false;
        }

        $version = $quote->activeVersionOrCurrent;

        $this->lockProvider
            ->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10)
            ->block(30, function () use ($version, $quoteFile, $importablePageNumber) {
                if (false === is_null($importablePageNumber)) {
                    $quoteFile->setImportedPage($importablePageNumber);
                }

                $quoteFile->clearException();

                if ($quoteFile->isPrice()) {
                    $version->priceList()->associate($quoteFile)->save();
                    $version->forgetCachedMappingReview();
                    $version->resetGroupDescription();
                }

                if ($quoteFile->isSchedule()) {
                    $version->paymentSchedule()->associate($quoteFile)->save();
                }
            });

        $this->documentProcessor->forwardProcessor($quoteFile);

        if ($quoteFile->isPrice() && $quoteFile->rowsData()->where('page', '>=', $quoteFile->imported_page)->doesntExist()) {
            $quoteFile->setException(QFNRF_02, 'QFNRF_02');
        }

        if ($quoteFile->isSchedule() && (is_null($quoteFile->scheduleData) || blank($quoteFile->scheduleData->value))) {
            $quoteFile->setException(QFNS_01, 'QFNS_01');
        }

        if ($quoteFile->isPrice()) {
            $this->guessQuoteMapping($quote);

            $this->lockProvider
                ->lock(Lock::UPDATE_QUOTE_FILE($quoteFile->getKey()), 10)
                ->block(30, function () use ($quoteFile) {
                    $quoteFile->automapped_at = now();

                    $this->connection->transaction(fn() => $quoteFile->save());
                });
        }

        return true;
    }

    public function guessQuoteMapping(Quote $quote): void
    {
        $activeVersion = $quote->activeVersionOrCurrent;

        /** @var ImportedRow|null $mappingRow */
        $mappingRow = $activeVersion->firstRow()->first();

        if (is_null($mappingRow) || is_null($mappingRow->columns_data)) {
            return;
        }

        $importableColumnKeys = $mappingRow->columns_data->pluck('importable_column_id')->all();

        $possibleMappingsFromBaseQuotes = FieldColumn::query()
            ->whereIn('importable_column_id', $importableColumnKeys)
            ->whereNotNull('template_field_id')
            ->groupBy('template_field_id', 'importable_column_id')
            ->orderByRaw('count(*) desc')
            ->select(['template_field_id', 'importable_column_id'])
            ->get()
            ->toBase();

        $possibleMappingsFromQuoteVersions = QuoteVersionFieldColumn::query()
            ->whereIn('importable_column_id', $importableColumnKeys)
            ->whereNotNull('template_field_id')
            ->groupBy('template_field_id', 'importable_column_id')
            ->orderByRaw('count(*) desc')
            ->select(['template_field_id', 'importable_column_id'])
            ->get()
            ->toBase();

        $possibleMappings = $possibleMappingsFromBaseQuotes->merge($possibleMappingsFromQuoteVersions);

        $guessedMapping = [];

        foreach ($importableColumnKeys as $columnKey) {

            /** @var DistributionFieldColumn|null $possibleMapping */
            $possibleMapping = $possibleMappings->first(function (Model $columnMapping) use ($columnKey) {
                return $columnMapping->importable_column_id === $columnKey;
            });

            if (false === is_null($possibleMapping)) {
                $guessedMapping[$possibleMapping->template_field_id] = [
                    'importable_column_id' => $columnKey,
                    'is_default_enabled' => false,
                    'is_preview_visible' => true,
                    'default_value' => null,
                    'sort' => null,
                ];
            }

        }

        if (empty($guessedMapping)) {
            return;
        }

        $this->lockProvider->lock(Lock::UPDATE_QUOTE($quote->getKey()), 10)
            ->block(30, function () use ($guessedMapping, $activeVersion) {

                $this->connection->transaction(fn() => $activeVersion->templateFields()->sync($guessedMapping));

            });
    }

    public function userQuery(): Builder
    {
        return tap(Quote::query(), function (Builder $query) {
            !app()->runningUnitTests() && $query->currentUserWhen(
                request()->user()->cant('view_quotes')
            );
        });
    }

    public function findOrNew(string $id): Quote
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Quote::query()->whereKey($id)->firstOrNew();
    }

    public function find(string $id): Quote
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Quote::query()->whereKey($id)->firstOrFail();
    }

    public function findVersion($model): BaseQuote
    {
        if ($model instanceof QuoteVersion) {
            return $model;
        }

        $quote = $this->resolveModel($model);

        return $quote->activeVersionOrCurrent;
    }

    public function create(array $attributes): Quote
    {
        return tap(new Quote, function (Quote $quote) use ($attributes) {
            $quote->fill($attributes);
            $quote->saveOrFail();
        });
    }

    public function discounts(string $id)
    {
        $quote = $this->findVersion($id);

        $discounts = MorphDiscount::query()
            ->whereHasMorph('discountable', $quote->discountsOrder(), fn($query) => $query->quoteAcceptable($quote)->activated())
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

        $providedDiscounts = MorphDiscount::query()->with('discountable')->whereIn('discountable_id', Arr::pluck($attributes, 'id'))
            ->select(['*', DB::raw("({$durationsSelect}) as `duration`")])
            ->orderByRaw("field(`discounts`.`discountable_type`, {$quote->discountsOrderToString()})", 'desc')
            ->get();

        /**
         * We are reassigning the Quote Discounts Relation for Calculation new Margin Percentage after provided Discounts applying.
         */
        $quote->discounts = $providedDiscounts;

        $quote->totalPrice = (float)$this->quoteQueries
            ->mappedSelectedRowsQuery($quote)
            ->sum('price');

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

    public function setVersion(string $versionId, $quote): bool
    {
        if (is_string($quote)) {
            $quote = $this->find($quote);
        }

        if (!$quote instanceof Quote) {
            return false;
        }

        $lock = $this->lockProvider->lock(Lock::UPDATE_QUOTE($quote->getKey()), 10);

        return $lock->block(30, function () use ($quote, $versionId) {
            $oldVersion = $quote->activeVersionOrCurrent;

            if ($oldVersion->getKey() === $versionId) {
                return false;
            }

            if ($quote->getKey() === $versionId) {
                $quote->activeVersion()->dissociate();
            } else {
                $quote->activeVersion()->associate($versionId);
            }

            $pass = $quote->save();

            $newVersion = $quote->refresh()->activeVersionOrCurrent;

            activity()
                ->on($quote)
                ->withAttribute(
                    'using_version',
                    $newVersion->versionName,
                    $oldVersion->versionName
                )
                ->queueWhen('updated', $pass);

            return $pass;
        });
    }

    public function createNewVersionIfNonCreator(Quote $quote): BaseQuote
    {
        /**
         * Create replicated version from using version in the following case:
         *
         * 1) Editor id !== $quote->user_id
         * 2) Using version doesn't belong to the current editor.
         *
         * If an editing version (which is using version) belongs to editor, the system shouldn't create a new version and continue modify current version.
         */
        if ($quote->exists && $quote->activeVersionOrCurrent->user_id !== auth()->id()) {
            $replicatedVersion = $this->replicateVersion($quote, $quote->activeVersionOrCurrent, auth()->user());

            $quote->activeVersion()->associate($replicatedVersion)->save();

            $quote->wasCreatedNewVersion = true;

            $quote->load('activeVersion');
        }

        return $quote->activeVersionOrCurrent;
    }

    public function model(): string
    {
        return Quote::class;
    }

    public function replicateDiscounts(BaseQuote $source, BaseQuote $target): void
    {
        $sourceTable = $source instanceof QuoteVersion ? 'quote_version_discount' : 'quote_discount';
        $sourceForeignKeyName = $source instanceof QuoteVersion ? 'quote_version_id' : 'quote_id';

        $targetTable = $target instanceof QuoteVersion ? 'quote_version_discount' : 'quote_discount';
        $targetForeignKeyName = $target instanceof QuoteVersion ? 'quote_version_id' : 'quote_id';

        $this->connection->table($targetTable)->insertUsing(
            [$targetForeignKeyName, 'discount_id', 'duration'],
            $this->connection->table($sourceTable)->select(
                DB::raw("'{$target->getKey()}' as $targetForeignKeyName"),
                'discount_id',
                'duration'
            )
                ->where($sourceForeignKeyName, $source->getKey())
        );
    }

    public function replicateMapping(BaseQuote $source, BaseQuote $target): void
    {
        $sourceTable = $source instanceof QuoteVersion ? 'quote_version_field_column' : 'quote_field_column';
        $sourceForeignKeyName = $source instanceof QuoteVersion ? 'quote_version_id' : 'quote_id';

        $targetTable = $target instanceof QuoteVersion ? 'quote_version_field_column' : 'quote_field_column';
        $targetForeignKeyName = $target instanceof QuoteVersion ? 'quote_version_id' : 'quote_id';

        $this->connection->table($targetTable)->insertUsing(
            [$targetForeignKeyName, 'template_field_id', 'importable_column_id', 'is_default_enabled', 'sort'],
            $this->connection->table($sourceTable)->select(
                DB::raw("'{$target->getKey()}' as $targetForeignKeyName"),
                'template_field_id',
                'importable_column_id',
                'is_default_enabled',
                'sort'
            )
                ->where($sourceForeignKeyName, $source->getKey())
        );
    }

    public function getQuotePermission(Quote $quote, array $permissions = ['*']): string
    {
        $base = Str::lower(Str::plural(class_basename(Quote::class)));

        $access = implode(',', array_map('trim', $permissions));

        return implode('.', [$base, $access, $quote->id]);
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

    protected function transitQuoteToVersion(Quote $quote, ?User $user = null)
    {
        $attributes = $quote->getAttributes();

        return (new QuoteVersion)
            ->setRawAttributes([
                'user_id' => optional($user)->getKey(),
                'quote_id' => $quote->getKey(),
                'customer_id' => Arr::get($attributes, 'customer_id'),
                'distributor_file_id' => Arr::get($attributes, 'distributor_file_id'),
                'schedule_file_id' => Arr::get($attributes, 'schedule_file_id'),
                'company_id' => Arr::get($attributes, 'company_id'),
                'vendor_id' => Arr::get($attributes, 'vendor_id'),
                'country_id' => Arr::get($attributes, 'country_id'),
                'quote_template_id' => Arr::get($attributes, 'quote_template_id'),
                'country_margin_id' => Arr::get($attributes, 'country_margin_id'),
                'source_currency_id' => Arr::get($attributes, 'source_currency_id'),
                'target_currency_id' => Arr::get($attributes, 'target_currency_id'),
                'completeness' => Arr::get($attributes, 'completeness'),
                'group_description' => Arr::get($attributes, 'group_description'),
                'sort_group_description' => Arr::get($attributes, 'sort_group_description'),
                'custom_discount' => Arr::get($attributes, 'custom_discount'),
                'buy_price' => Arr::get($attributes, 'buy_price'),
                'exchange_rate_margin' => Arr::get($attributes, 'exchange_rate_margin'),
                'calculate_list_price' => Arr::get($attributes, 'calculate_list_price'),
                'use_groups' => Arr::get($attributes, 'use_groups'),
                // 'version_number'         => Arr::get($attributes, '____________'),
                'pricing_document' => Arr::get($attributes, 'pricing_document'),
                'service_agreement_id' => Arr::get($attributes, 'service_agreement_id'),
                'system_handle' => Arr::get($attributes, 'system_handle'),
                'additional_details' => Arr::get($attributes, 'additional_details'),
                'additional_notes' => Arr::get($attributes, 'additional_notes'),
                'closing_date' => Arr::get($attributes, 'closing_date'),
                'previous_state' => Arr::get($attributes, 'previous_state'),
                'checkbox_status' => Arr::get($attributes, 'checkbox_status'),
            ]);
    }

    protected function transitVersionToQuote(QuoteVersion $version, Quote $quote)
    {
        $versionAttributes = $version->getAttributes();
        $quoteAttributes = $quote->getAttributes();

        return (new Quote)
            ->setRawAttributes([

                // 'active_version_id'      => Arr::get($attributes, '___'),
                'user_id' => Arr::get($versionAttributes, 'user_id'),
                'quote_template_id' => Arr::get($versionAttributes, 'quote_template_id'),
                'contract_template_id' => Arr::get($quoteAttributes, 'contract_template_id'),
                'company_id' => Arr::get($versionAttributes, 'company_id'),
                'vendor_id' => Arr::get($versionAttributes, 'vendor_id'),
                'country_id' => Arr::get($versionAttributes, 'country_id'),
                'customer_id' => Arr::get($versionAttributes, 'customer_id'),
                'schedule_file_id' => Arr::get($versionAttributes, 'schedule_file_id'),
                'distributor_file_id' => Arr::get($versionAttributes, 'distributor_file_id'),
                'previous_state' => Arr::get($versionAttributes, 'previous_state'),
                'country_margin_id' => Arr::get($versionAttributes, 'country_margin_id'),
                'completeness' => Arr::get($versionAttributes, 'completeness'),
                'pricing_document' => Arr::get($versionAttributes, 'pricing_document'),
                'service_agreement_id' => Arr::get($versionAttributes, 'service_agreement_id'),
                'system_handle' => Arr::get($versionAttributes, 'system_handle'),
                'additional_details' => Arr::get($versionAttributes, 'additional_details'),
                'checkbox_status' => Arr::get($versionAttributes, 'checkbox_status'),
                'closing_date' => Arr::get($versionAttributes, 'closing_date'),
                'additional_notes' => Arr::get($versionAttributes, 'additional_notes'),
                'calculate_list_price' => Arr::get($versionAttributes, 'calculate_list_price'),
                'buy_price' => Arr::get($versionAttributes, 'buy_price'),
                'exchange_rate_margin' => Arr::get($versionAttributes, 'exchange_rate_margin'),
                'custom_discount' => Arr::get($versionAttributes, 'custom_discount'),
                'group_description' => Arr::get($versionAttributes, 'group_description'),
                'use_groups' => Arr::get($versionAttributes, 'use_groups'),
                'sort_group_description' => Arr::get($versionAttributes, 'sort_group_description'),
                'source_currency_id' => Arr::get($versionAttributes, 'source_currency_id'),
                'target_currency_id' => Arr::get($versionAttributes, 'target_currency_id'),
                'created_at' => Arr::get($versionAttributes, 'created_at'),
                'updated_at' => Arr::get($quoteAttributes, 'updated_at'),
                'deleted_at' => Arr::get($quoteAttributes, 'deleted_at'),
                'activated_at' => Arr::get($quoteAttributes, 'activated_at'),
                'submitted_at' => Arr::get($quoteAttributes, 'submitted_at'),
                'assets_migrated_at' => Arr::get($quoteAttributes, 'assets_migrated_at'),
            ]);
    }

    public function replicateQuote(Quote $quote): Quote
    {
        $this->connection->beginTransaction();

        try {
            $version = $quote->activeVersionOrCurrent;

            $replicatedQuote = $version instanceof QuoteVersion
                ? $this->transitVersionToQuote($version, $quote)
                : $version->replicate(['is_active']);

            // Deactivate the quote.
            $quote->forceFill(['activated_at' => null])->save();

            // Unravel the replicated quote.
            $replicatedQuote->forceFill(['submitted_at' => null])->save();

            $this->replicateDiscounts($version, $replicatedQuote);
            $this->replicateMapping($version, $replicatedQuote);

            if ($version->priceList->exists) {
                $priceListFile = $this->quoteFileRepository->replicatePriceList($version->priceList);

                $replicatedQuote->distributor_file_id = $priceListFile->getKey();
            }

            if ($version->paymentSchedule->exists) {
                tap($version->paymentSchedule->replicate(), function ($schedule) use ($replicatedQuote, $version) {
                    $schedule->save();
                    $schedule->scheduleData()->save($version->paymentSchedule->scheduleData->replicate());
                    $replicatedQuote->schedule_file_id = $schedule->getKey();
                });
            }

            $replicatedQuote->save();

            $this->connection->commit();

            activity()
                ->on($replicatedQuote)
                ->withProperties(['old' => Quote::logChanges($version), 'attributes' => Quote::logChanges($replicatedQuote)])
                ->by(request()->user())
                ->queue('copied');

            return $replicatedQuote;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    protected function replicateVersion(Quote $parent, BaseQuote $version, ?User $user = null): BaseQuote
    {
        $this->connection->beginTransaction();

        try {
            /** @var QuoteVersion */
            $replicatedVersion = $version instanceof QuoteVersion
                ? $version->replicate()
                : $this->transitQuoteToVersion($version, $user);

            if (isset($user)) {
                $replicatedVersion->user()->associate($user);
            }

            $replicatedVersion->version_number = $this->countVersionNumber($parent, $replicatedVersion);

            $pass = $replicatedVersion->save();

            /** Discounts Replication. */
            $this->replicateDiscounts($version, $replicatedVersion);

            /** Mapping Replication. */
            $this->replicateMapping($version, $replicatedVersion);

            if ($version->priceList->exists) {
                $priceListFile = $this->quoteFileRepository->replicatePriceList($version->priceList);

                $replicatedVersion->distributor_file_id = $priceListFile->getKey();
            }

            if (!is_null($version->paymentSchedule) && $version->paymentSchedule->exists && !is_null($version->paymentSchedule->scheduleData)) {
                tap($version->paymentSchedule->replicate(), function ($schedule) use ($replicatedVersion, $version) {
                    $schedule->save();
                    $schedule->scheduleData()->save($version->paymentSchedule->scheduleData->replicate());
                    $replicatedVersion->schedule_file_id = $schedule->getKey();
                });
            }

            if ($version->group_description->isNotEmpty()) {
                $rowsIds = $replicatedVersion->groupedRows();

                /** @var \Illuminate\Database\Eloquent\Collection */
                $replicatedRows = $replicatedVersion->rowsData()->getQuery()->toBase()->whereIn('imported_rows.replicated_row_id', $rowsIds)->get(['imported_rows.id', 'imported_rows.replicated_row_id']);

                $replicatedVersion->group_description->each(function (RowsGroup $group) use ($replicatedRows) {
                    $rowsIds = $replicatedRows->whereIn('replicated_row_id', $group->rows_ids)->pluck('id');

                    $group->rows_ids = $rowsIds->toArray();
                });
            }

            $pass = $replicatedVersion->save();

            $this->connection->commit();

            activity()
                ->on($replicatedVersion)
                ->withProperties(['old' => Quote::logChanges($version), 'attributes' => Quote::logChanges($replicatedVersion)])
                ->queue('created_version');

            return $replicatedVersion;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    protected function setMargin(Collection $state, BaseQuote $quote): void
    {
        if ((bool)data_get($state, 'margin.delete', false) === true) {
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

        /** @var CountryMargin $countryMargin */
        $countryMargin = CountryMargin::query()
            ->where('vendor_id', $quote->vendor_id)
            ->where('country_id', $quote->country_id)
            ->where('quote_type', $state['margin']['quote_type'] ?? null)
            ->where('is_fixed', $state['margin']['is_fixed'] ?? null)
            ->where('value', $state['margin']['value'] ?? null)
            ->where('method', $state['margin']['method'] ?? null)
            ->firstOr(function () use ($state, $quote) {
                return tap(new CountryMargin(), function (CountryMargin $countryMargin) use ($quote, $state) {
                    $countryMargin->vendor_id = $quote->vendor_id;
                    $countryMargin->country_id = $quote->country_id;
                    $countryMargin->quote_type = $state['margin']['quote_type'] ?? null;
                    $countryMargin->is_fixed = $state['margin']['is_fixed'] ?? null;
                    $countryMargin->value = $state['margin']['value'] ?? null;
                    $countryMargin->method = $state['margin']['method'] ?? null;

                    $countryMargin->save();
                });
            });

        if ($countryMargin->getKey() === $quote->country_margin_id) {
            return;
        }

        $quote->countryMargin()->associate($countryMargin);
        // $quote->margin_data = array_merge($countryMargin->only('value', 'method', 'is_fixed'), ['type' => 'By Country']);
        // $quote->type = data_get($state, 'margin.quote_type');
        $quote->save();

        /**
         * Fresh Discounts Margin Percentage.
         */
        $this->freshDiscounts($quote);
    }

    protected function setDiscounts(Collection $state, BaseQuote $quote): void
    {
        if ((bool)$state->get('discounts_detach') === true) {
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

    /**
     * Returns true if quote was submitted.
     *
     * @param Collection $state
     * @param Quote $quote
     * @return bool
     */
    protected function draftOrSubmit(Collection $state, Quote $quote): bool
    {
        if ((bool)$state->get('save') === false) {
            $quote->save();

            return false;
        }

        tap($quote, function (Quote $quote) {
            $quote->submitted_at = $quote->freshTimestampString();

            $quote->save();
        });

        return true;
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

        $this->connection->transaction(function () use ($quote, $updatableScope, $selectedRowsIds) {
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

        $this->busDispatcher->dispatch(new RetrievePriceAttributes($quote));
        $this->busDispatcher->dispatch(new MigrateQuoteAssets($quote));

        /**
         * Clear Cache Mapping Review Data when Mapping was changed.
         */
        $quote->forgetCachedMappingReview();
    }

    protected function storeQuoteFilesState(Collection $state, BaseQuote $quote): void
    {
        if (blank($stateFiles = collect(data_get($state, 'quote_data.files')))) {
            return;
        }

        $distributorFileKey = $quote->getAttribute($quote->priceList()->getForeignKeyName());
        $paymentScheduleFileKey = $quote->getAttribute($quote->paymentSchedule()->getForeignKeyName());

        $stateQuoteFiles = QuoteFile::query()->whereKey($stateFiles->all())->get()->keyBy('file_type');
        $stateDistributorFile = $stateQuoteFiles->get(QFT_PL);
        $statePaymentScheduleFile = $stateQuoteFiles->get(QFT_PS);

        if (
            $stateDistributorFile === $distributorFileKey
            && $statePaymentScheduleFile === $paymentScheduleFileKey
        ) {
            return;
        }

        $originalQuoteFiles = collect([$quote->priceList, $quote->paymentSchedule])->pluck('original_file_name')->filter()->implode(', ');
        $newQuoteFiles = $stateQuoteFiles->pluck('original_file_name')->filter()->implode(', ');

        $quote->priceList()->associate($stateDistributorFile);
        $quote->paymentSchedule()->associate($statePaymentScheduleFile);
        $quote->save();

        $quote->setRelation('priceList', $stateDistributorFile);
        $quote->setRelation('paymentSchedule', $statePaymentScheduleFile);

        activity()
            ->performedOn($quote)
            ->withAttribute('quote_files', $newQuoteFiles, $originalQuoteFiles)
            ->queue('updated');
    }

    protected function detachScheduleIfRequested(Collection $state, BaseQuote $quote): void
    {
        if (!data_get($state, 'quote_data.detach_schedule', false) || !$quote->paymentSchedule()->exists()) {
            return;
        }

        $quote->paymentSchedule()->dissociate()->save();
    }

    protected function setComputableRows(BaseQuote $quote): void
    {
        $rows = (new QuoteQueries)->mappedOrderedRowsQuery($quote)->where('is_selected', true)->get();
        $rows = MappedRows::make($rows);

        $quote->computableRows = $rows;
    }

    private function countVersionNumber(Quote $quote, QuoteVersion $version): int
    {
        $count = $quote->versions()->where('user_id', $version->user_id)->count();

        /**
         * We are incrementing a new version on 2 point if parent author equals a new version author as we are not record original version to the pivot table.
         * Incrementing number equals 1 if there is a new version from non-author.
         */
        if ($quote->user_id === $version->user_id) {
            $count++;
        }

        return ++$count;
    }

    /**
     * @inheritDoc
     */
    public function processQuoteUnravel(Quote $quote): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWQUOTE($quote->getKey())
        );

        $quote->submitted_at = null;

        $lock->block(30, function () use ($quote) {
            $this->connection->transaction(fn() => $quote->save());
        });

        $this->eventDispatcher->dispatch(new RescueQuoteUnravelled($quote));

        activity()->on($quote)->queue('unravel');
    }
}
