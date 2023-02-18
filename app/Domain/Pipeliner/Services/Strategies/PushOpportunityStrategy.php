<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Company\Models\Company;
use App\Domain\Currency\Models\Currency;
use App\Domain\Pipeliner\Events\SyncStrategyPerformed;
use App\Domain\Pipeliner\Integration\Enum\ValidationLevel;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerClientIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunitySharingClientRelationIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerPipelineIntegration;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\OpportunityEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunitySharingClientRelationEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunitySharingClientRelationFilterInput;
use App\Domain\Pipeliner\Integration\Models\ValidationLevelCollection;
use App\Domain\Pipeliner\Models\PipelinerModelUpdateLog;
use App\Domain\Pipeliner\Models\PipelinerSyncStrategyLog;
use App\Domain\Pipeliner\Services\Exceptions\PipelinerSyncException;
use App\Domain\Pipeliner\Services\PipelinerAccountLookupService;
use App\Domain\Pipeliner\Services\PipelinerOpportunityLookupService;
use App\Domain\Pipeliner\Services\PipelinerSyncAggregate;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PushStrategy;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Services\ApplicationUserResolver;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Services\Opportunity\Exceptions\OpportunityDataMappingException;
use App\Domain\Worldwide\Services\Opportunity\OpportunityDataMapper;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

class PushOpportunityStrategy implements PushStrategy
{
    use SalesUnitsAware;

    protected array $options = [];

    public function __construct(
        protected ConnectionInterface $connection,
        protected PipelinerAccountLookupService $accountLookupService,
        protected PipelinerOpportunityLookupService $opportunityLookupService,
        protected PipelinerPipelineIntegration $pipelineIntegration,
        protected PipelinerAccountIntegration $accountIntegration,
        protected PipelinerOpportunityIntegration $oppIntegration,
        protected PipelinerOpportunitySharingClientRelationIntegration $sharingClientRelationIntegration,
        protected PipelinerClientIntegration $clientIntegration,
        protected PushSalesUnitStrategy $pushSalesUnitStrategy,
        protected PushClientStrategy $pushClientStrategy,
        protected PushCurrencyStrategy $pushCurrencyStrategy,
        protected PushContactStrategy $pushContactStrategy,
        protected PushCompanyStrategy $pushCompanyStrategy,
        protected PushNoteStrategy $pushNoteStrategy,
        protected PushTaskStrategy $pushTaskStrategy,
        protected PushAttachmentStrategy $pushAttachmentStrategy,
        protected PushAppointmentStrategy $pushAppointmentStrategy,
        protected OpportunityDataMapper $dataMapper,
        protected ApplicationUserResolver $defaultUserResolver,
        protected Cache $cache,
        protected LockProvider $lockProvider,
        protected EventDispatcher $eventDispatcher,
        protected PipelinerSyncAggregate $syncAggregate,
    ) {
    }

    /**
     * @throws PipelinerSyncException
     */
    private function modelsToBeUpdatedQuery(): Builder
    {
        $updateLogModel = new PipelinerModelUpdateLog();

        $lastOpportunityUpdatedAt = $updateLogModel->newQuery()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Opportunity();
        $salesUnitModel = new SalesUnit();

        $syncStrategyLogModel = new PipelinerSyncStrategyLog();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->getQualifiedUpdatedAtColumn(),
                $model->primaryAccount()->getQualifiedForeignKeyName(),
                $model->endUser()->getQualifiedForeignKeyName(),
                $model->qualifyColumn('pl_reference'),
                $model->qualifyColumn('project_name'),
                $salesUnitModel->qualifyColumn('unit_name'),
            ])
            ->orderBy($model->getQualifiedUpdatedAtColumn())
            ->join($salesUnitModel->getTable(), $model->salesUnit()->getQualifiedForeignKeyName(), $salesUnitModel->getQualifiedKeyName())
            ->whereIn($model->salesUnit()->getQualifiedForeignKeyName(),
                Collection::make($this->getSalesUnits())->modelKeys())
            ->where(static function (Builder $builder) use ($model): void {
                $builder->whereColumn($model->getQualifiedUpdatedAtColumn(), '>', $model->getQualifiedCreatedAtColumn())
                    ->orWhereNull($model->qualifyColumn('pl_reference'));
            })
            ->leftJoinSub(
                $syncStrategyLogModel->newQuery()
                    ->selectRaw("max({$syncStrategyLogModel->getQualifiedUpdatedAtColumn()}) as {$syncStrategyLogModel->getUpdatedAtColumn()}")
                    ->addSelect($syncStrategyLogModel->model()->getQualifiedForeignKeyName())
                    ->where($syncStrategyLogModel->qualifyColumn('strategy_name'),
                        (string) StrategyNameResolver::from($this))
                    ->groupBy($syncStrategyLogModel->model()->getQualifiedForeignKeyName()),
                'latest_sync_strategy_log',
                "latest_sync_strategy_log.{$syncStrategyLogModel->model()->getForeignKeyName()}",
                $model->getQualifiedKeyName(),
            )
            ->where(static function (Builder $builder) use ($syncStrategyLogModel, $model): void {
                $builder
                    ->whereNull("latest_sync_strategy_log.{$syncStrategyLogModel->getUpdatedAtColumn()}")
                    ->orWhereColumn($model->getQualifiedUpdatedAtColumn(), '>',
                        "latest_sync_strategy_log.{$syncStrategyLogModel->getUpdatedAtColumn()}");
            })
            ->whereSyncNotProtected()
            ->whereDoesntHave('syncErrors', static function (Builder $builder): void {
                $builder->whereNull('resolved_at')
                    ->whereNotNull('archived_at');
            })
            ->unless(is_null($lastOpportunityUpdatedAt), static function (Builder $builder) use (
                $model,
                $lastOpportunityUpdatedAt
            ): void {
                $builder->where($model->getQualifiedUpdatedAtColumn(), '>', $lastOpportunityUpdatedAt);
            });
    }

    /**
     * @throws PipelinerSyncException
     */
    public function countPending(): int
    {
        return $this->modelsToBeUpdatedQuery()->count();
    }

    /**
     * @throws PipelinerSyncException
     */
    public function iteratePending(): \Traversable
    {
        return $this->modelsToBeUpdatedQuery()
            ->lazy()
            ->map(static function (Opportunity $model): array {
                $withoutOverlapping = collect([
                    $model->primaryAccount()->getParentKey(),
                    $model->endUser()->getParentKey(),
                ])
                    ->lazy()
                    ->filter(static fn (?string $id): bool => $id !== null)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => $model->getKey(),
                    'pl_reference' => $model->pl_reference,
                    'modified' => $model->{$model->getUpdatedAtColumn()}?->toIso8601String(),
                    'name' => $model->project_name,
                    'unit_name' => $model->unit_name,
//                    'without_overlapping' => $withoutOverlapping,
                ];
            });
    }

    /**
     * @param Opportunity $model
     *
     * @throws \App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException
     * @throws \App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model, mixed ...$options): void
    {
        $this->options = $options;

        if (!$model instanceof Opportunity) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', Opportunity::class));
        }

        if ($model->getFlag(Opportunity::SYNC_PROTECTED)) {
            throw PipelinerSyncException::modelProtectedFromSync($model)->relatedTo($model);
        }

        if (null === $model->pipelineStage) {
            throw new PipelinerSyncException("Opportunity [{$model->getIdForHumans()}] doesn't have assigned pipeline stage.");
        }

        if (null === $model->salesUnit) {
            throw PipelinerSyncException::modelDoesntHaveUnitRelation($model)->relatedTo($model);
        }

        if (!$model->salesUnit->is_enabled) {
            throw PipelinerSyncException::modelBelongsToDisabledUnit($model, $model->salesUnit)->relatedTo($model);
        }

        if (is_null($model->pl_reference)) {
            $oppEntity = $this->opportunityLookupService->find($model, $model->salesUnit);

            if (null !== $oppEntity) {
                $model->pl_reference = $oppEntity->id;

                tap($model, function (Opportunity $opportunity): void {
                    $this->connection->transaction(static fn () => $opportunity->saveQuietly());
                });
            }
        }

        if (null !== $model->salesUnit) {
            $this->pushSalesUnitStrategy->sync($model->salesUnit);
        }

        // Pushing owner, account, contact entities at first,
        // as we map their ids to the opportunity entity.
        $this->pushCurrenciesOfOppty($model);
        $this->pushOwnerOfOppty($model);
        $this->pushAccountsFromOppty($model);
        $this->pushAttachmentsFromOppty($model);

        if (is_null($model->pl_reference)) {
            try {
                $input = $this->dataMapper->mapPipelinerCreateOpportunityInput(opportunity: $model);
            } catch (OpportunityDataMappingException $e) {
                throw new PipelinerSyncException($e->getMessage(), previous: $e);
            }

            $oppEntity = $this->oppIntegration->create($input,
                ValidationLevelCollection::from(
                    ValidationLevel::SKIP_USER_DEFINED_VALIDATIONS,
                    ValidationLevel::SKIP_FIELD_VALUE_VALIDATION,
                    ValidationLevel::SKIP_UNCHANGED_FIELDS
                ));

            tap($model, function (Opportunity $opportunity) use ($oppEntity): void {
                $opportunity->pl_reference = $oppEntity->id;

                $this->connection->transaction(static fn () => $opportunity->push());
            });
        } else {
            $oppEntity = $this->oppIntegration->getById($model->pl_reference);
            $sharingClients = $this->collectSharingClientRelationsFromOpportunityEntity($oppEntity);

            try {
                $input = $this->dataMapper->mapPipelinerUpdateOpportunityInput(
                    opportunity: $model,
                    oppEntity: $oppEntity,
                    sharingClientRelations: $sharingClients,
                );
            } catch (OpportunityDataMappingException $e) {
                throw new PipelinerSyncException($e->getMessage(), previous: $e);
            }

            $modifiedFields = $input->getModifiedFields();

            if (false === empty($modifiedFields)) {
                $this->oppIntegration->update($input,
                    ValidationLevelCollection::from(
                        ValidationLevel::SKIP_USER_DEFINED_VALIDATIONS,
                        ValidationLevel::SKIP_FIELD_VALUE_VALIDATION,
                        ValidationLevel::SKIP_UNCHANGED_FIELDS
                    ));
            }
        }

        // Pushing the note, task, appointment entities at last,
        // as they are dependent on the existing opportunity entity.
        $tasks = [
            fn () => $this->pushNotesFromOppty($model),
            fn () => $this->pushTasksFromOppty($model),
            fn () => $this->pushAppointmentsFromOppty($model),
        ];

        collect($tasks)->each(static function (callable $task): void {
            $task();
        });

        $this->persistSyncLog($model);
        $this->eventDispatcher->dispatch(
            new SyncStrategyPerformed(
                model: $model,
                strategyClass: static::class,
                aggregateId: $this->syncAggregate->id,
            )
        );
    }

    private function persistSyncLog(Model $model): void
    {
        tap(new PipelinerSyncStrategyLog(), function (PipelinerSyncStrategyLog $log) use ($model) {
            $log->model()->associate($model);
            $log->strategy_name = (string) StrategyNameResolver::from($this);
            $log->save();
        });
    }

    public function getModelType(): string
    {
        return (new Opportunity())->getMorphClass();
    }

    /**
     * @return list<OpportunitySharingClientRelationEntity>
     */
    private function collectSharingClientRelationsFromOpportunityEntity(OpportunityEntity $entity): array
    {
        $iterator = $this->sharingClientRelationIntegration->scroll(
            filter: OpportunitySharingClientRelationFilterInput::new()
                ->leadOpptyId(
                    EntityFilterStringField::eq($entity->id)
                )
        );

        return LazyCollection::make(static function () use ($iterator): \Generator {
            yield from $iterator;
        })
            ->values()
            ->all();
    }

    private function pushCurrenciesOfOppty(Opportunity $opportunity): void
    {
        $currencyCodes = collect([
            $opportunity->estimated_upsell_amount_currency_code,
            $opportunity->list_price_currency_code,
            $opportunity->opportunity_amount_currency_code,
            $opportunity->purchase_price_currency_code,
        ])
            ->filter(static fn (?string $code): bool => null !== $code)
            ->unique()
            ->values()
            ->all();

        Currency::query()
            ->whereIn('code', $currencyCodes)
            ->get()
            ->each(function (Currency $currency): void {
                $this->pushCurrencyStrategy->sync($currency);
            });
    }

    private function pushOwnerOfOppty(Opportunity $opportunity): void
    {
        if (null === $opportunity->owner) {
            $opportunity->owner()->associate($this->defaultUserResolver->resolve());

            $this->connection->transaction(static fn () => $opportunity->saveQuietly());
        }

        if (null !== $opportunity->owner) {
            $this->pushClientStrategy->sync($opportunity->owner);
        }
    }

    private function pushAccountsFromOppty(Opportunity $opportunity): void
    {
        if (null !== $opportunity->primaryAccount) {
            $this->pushAccount($opportunity->primaryAccount);
        }

        if (null !== $opportunity->endUser && $opportunity->endUser->isNot($opportunity->primaryAccount)) {
            $this->pushAccount($opportunity->endUser);
        }

        $opportunity->load(['primaryAccount', 'endUser']);
    }

    private function pushAccount(Company $company): void
    {
        if ($this->hasBatchId()) {
            $key = static::class.$this->getBatchId().$this->pushCompanyStrategy::class.$company->getKey();

            $this->lockProvider->lock($key, 60 * 8)
                ->block(60 * 8, function () use ($company, $key) {
                    $this->cache->remember(
                        key: $key.'sync',
                        ttl: now()->addHours(8),
                        callback: function () use ($company): bool {
                            $this->pushCompanyStrategy
                                ->setSalesUnits(...$this->getSalesUnits())
                                ->sync($company);

                            return true;
                        }
                    );
                });

            $company->refresh();

            return;
        }

        $this->pushCompanyStrategy
            ->setSalesUnits(...$this->getSalesUnits())
            ->sync($company);
    }

    private function pushAttachmentsFromOppty(Opportunity $opportunity): void
    {
        foreach ($opportunity->attachments as $attachment) {
            $this->pushAttachmentStrategy->sync($attachment);
        }
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Opportunity;
    }

    private function pushNotesFromOppty(Opportunity $opportunity): void
    {
        foreach ($opportunity->notes()->lazyById(100) as $item) {
            $this->pushNoteStrategy->sync($item);
        }
    }

    private function pushTasksFromOppty(Opportunity $opportunity): void
    {
        foreach ($opportunity->tasks()->lazyById(100) as $item) {
            $this->pushTaskStrategy->sync($item);
        }
    }

    private function pushAppointmentsFromOppty(Opportunity $opportunity): void
    {
        foreach ($opportunity->ownAppointments()->lazyById(100) as $item) {
            $this->pushAppointmentStrategy->sync($item);
        }
    }

    private function getBatchId(): ?string
    {
        return $this->options['batchId'] ?? null;
    }

    private function hasBatchId(): bool
    {
        return $this->getBatchId() !== null;
    }

    public function getByReference(string $reference): object
    {
        return Opportunity::query()->findOrFail($reference);
    }
}
