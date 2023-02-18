<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Company\Models\Company;
use App\Domain\Pipeliner\Events\SyncStrategyPerformed;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAppointmentIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerNoteIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunitySharingClientRelationIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerPipelineIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerTaskIntegration;
use App\Domain\Pipeliner\Integration\Models\ActivityRelationFilterInput;
use App\Domain\Pipeliner\Integration\Models\AppointmentFilterInput;
use App\Domain\Pipeliner\Integration\Models\ContactRelationEntity;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\LeadOpptyAccountRelationEntity;
use App\Domain\Pipeliner\Integration\Models\NoteFilterInput;
use App\Domain\Pipeliner\Integration\Models\OpportunityEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunityFilterInput;
use App\Domain\Pipeliner\Integration\Models\OpportunitySharingClientRelationEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunitySharingClientRelationFilterInput;
use App\Domain\Pipeliner\Integration\Models\SalesUnitFilterInput;
use App\Domain\Pipeliner\Integration\Models\TaskFilterInput;
use App\Domain\Pipeliner\Models\PipelinerModelScrollCursor;
use App\Domain\Pipeliner\Models\PipelinerSyncStrategyLog;
use App\Domain\Pipeliner\Services\Exceptions\PipelinerSyncException;
use App\Domain\Pipeliner\Services\PipelinerSyncAggregate;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PullStrategy;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Sync\Enum\Lock;
use App\Domain\Worldwide\Events\Opportunity\OpportunityCreated;
use App\Domain\Worldwide\Events\Opportunity\OpportunityUpdated;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Services\Opportunity\OpportunityDataMapper;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;
use JetBrains\PhpStorm\ArrayShape;
use function React\Async\async;
use function React\Async\await;
use function React\Async\parallel;

class PullOpportunityStrategy implements PullStrategy
{
    use SalesUnitsAware;

    protected array $options = [];

    public function __construct(
        protected ConnectionInterface $connection,
        protected EventDispatcher $eventDispatcher,
        protected PipelinerPipelineIntegration $pipelineIntegration,
        protected PipelinerOpportunityIntegration $oppIntegration,
        protected PipelinerOpportunitySharingClientRelationIntegration $sharingClientRelationIntegration,
        protected PullCompanyStrategy $pullCompanyStrategy,
        protected PullTaskStrategy $pullTaskStrategy,
        protected PullAppointmentStrategy $pullAppointmentStrategy,
        protected PullNoteStrategy $pullNoteStrategy,
        protected PullAttachmentStrategy $pullAttachmentStrategy,
        protected PipelinerAppointmentIntegration $appointmentIntegration,
        protected PipelinerTaskIntegration $taskIntegration,
        protected PipelinerNoteIntegration $noteIntegration,
        protected OpportunityDataMapper $oppDataMapper,
        protected Cache $cache,
        protected LockProvider $lockProvider,
        protected PipelinerSyncAggregate $syncAggregate,
    ) {
    }

    /**
     * @throws PipelinerSyncException
     */
    public function countPending(): int
    {
        [$count, $lastId] = $this->computeTotalEntitiesCountAndLastIdToPull();

        return $count;
    }

    public function iteratePending(): \Traversable
    {
        return LazyCollection::make(function (): \Generator {
            yield from $this->oppIntegration->simpleScroll(
                ...$this->resolveScrollParameters(),
                ...['first' => 2_000]
            );
        })
            ->filter(function (array $item): bool {
                return $this->isStrategyYetToBeAppliedTo($item['id'], $item['modified']);
            })
            ->map(static function (array $item): array {
                return [
                    'id' => $item['id'],
                    'pl_reference' => $item['id'],
                    'name' => $item['name'],
                    'modified' => $item['modified'],
                    'unit_name' => $item['unit']['name'],
                ];
            });
    }

    /**
     * @param OpportunityEntity $entity
     *
     * @return \App\Domain\Worldwide\Models\Opportunity
     */
    public function sync(object $entity, mixed ...$options): Model
    {
        if (!$entity instanceof OpportunityEntity) {
            throw new \TypeError(sprintf('Entity must be an instance of %s.', OpportunityEntity::class));
        }

        $this->options = $options;

        $lock = $this->lockProvider->lock(Lock::SYNC_OPPORTUNITY($entity->id), 30);

        $sharingClients = $this->collectSharingClientRelationsFromOpportunityEntity($entity);

        $opportunity = $lock->block(30, function () use ($entity, $sharingClients): Opportunity {
            /** @var \App\Domain\Worldwide\Models\Opportunity|null $opportunity */
            $opportunity = Opportunity::query()
                ->withTrashed()
                ->where('pl_reference', $entity->id)
                ->first();

            if (null === $opportunity) {
                $opportunity = $this->performOpportunityLookup($entity);
            }

            if ($opportunity !== null && $opportunity->getFlag(Opportunity::SYNC_PROTECTED)) {
                throw PipelinerSyncException::modelProtectedFromSync($opportunity)->relatedTo($opportunity);
            }

            if ($opportunity !== null && !$opportunity->salesUnit->is_enabled) {
                throw PipelinerSyncException::modelBelongsToDisabledUnit($opportunity, $opportunity->salesUnit)->relatedTo($opportunity);
            }

            /** @var Collection|Company[] $accounts */
            $accounts = Collection::make($entity->accountRelations)
                ->map(function (LeadOpptyAccountRelationEntity $relationEntity) use ($entity): Company {
                    $contactRelations = $relationEntity->isPrimary ? $entity->contactRelations : [];

                    if ($this->hasBatchId()) {
                        $contactRelationsHash = $this->computeContactRelationsHash($contactRelations);

                        $key = $this->pullCompanyStrategy::class.$this->getBatchId().$relationEntity->account->id.$contactRelationsHash;

                        $id = $this->lockProvider->lock($key, 240)
                            ->block(240, function () use ($contactRelations, $relationEntity, $key) {
                                return $this->cache->remember(
                                    key: $key.'result',
                                    ttl: now()->addHours(8),
                                    callback: fn (): string => $this->pullCompanyStrategy->sync(
                                        $relationEntity->account,
                                        contactRelations: $contactRelations,
                                        batchId: $this->getBatchId(),
                                    )->getKey());
                            });

                        /* @noinspection PhpIncompatibleReturnTypeInspection */
                        return Company::query()->findOrFail($id);
                    }

                    return $this->pullCompanyStrategy->sync(
                        $relationEntity->account,
                        contactRelations: $contactRelations,
                        batchId: $this->getBatchId(),
                    );
                });

            $newOpportunity = $this->oppDataMapper->mapOpportunityFromOpportunityEntity($entity, $accounts,
                $sharingClients);

            // Merge attributes when a model exists already.
            if (null !== $opportunity) {
                $oldOpportunity = (new Opportunity())->setRawAttributes($opportunity->getRawOriginal());

                $this->oppDataMapper->mergeAttributesFrom($opportunity, $newOpportunity);

                $this->connection->transaction(static function () use ($opportunity): void {
                    $opportunity->withoutTimestamps(static function (Opportunity $opportunity): void {
                        $opportunity->push();
                        $opportunity->sharingUsers()->sync($opportunity->sharingUsers);
                    });
                });

                $this->syncRelationsOfOpportunityEntity($entity, $opportunity);

                $this->eventDispatcher->dispatch(
                    new OpportunityUpdated($opportunity, $oldOpportunity)
                );

                return $opportunity;
            }

            $this->connection->transaction(static function () use ($newOpportunity): void {
                $newOpportunity->push();
                $newOpportunity->sharingUsers()->sync($newOpportunity->sharingUsers);
            });

            $this->syncRelationsOfOpportunityEntity($entity, $newOpportunity);

            $this->eventDispatcher->dispatch(
                new OpportunityCreated($newOpportunity)
            );

            return $newOpportunity;
        });

        $this->persistSyncLog($opportunity);
        $this->eventDispatcher->dispatch(
            new SyncStrategyPerformed(
                model: $opportunity,
                strategyClass: static::class,
                aggregateId: $this->syncAggregate->id,
            )
        );

        return $opportunity;
    }

    private function persistSyncLog(Model $model): void
    {
        tap(new PipelinerSyncStrategyLog(), function (PipelinerSyncStrategyLog $log) use ($model) {
            $log->model()->associate($model);
            $log->strategy_name = (string) StrategyNameResolver::from($this);
            $log->save();
        });
    }

    private function computeContactRelationsHash(array $contactRelations): string
    {
        return collect($contactRelations)
            ->sortBy(static function (ContactRelationEntity $entity): string {
                return $entity->contact->id;
            })
            ->map(static function (ContactRelationEntity $entity): string {
                return $entity->contact->id.$entity->isPrimary;
            })
            ->pipe(static function (BaseCollection $collection): string {
                return sha1($collection->join('.'));
            });
    }

    /**
     * @throws PipelinerSyncException
     */
    protected function performOpportunityLookup(OpportunityEntity $entity): ?Opportunity
    {
        /** @var SalesUnit $unit */
        $unit = SalesUnit::query()->where('unit_name', $entity->unit->name)->sole();

        $matchingOpportunities = Opportunity::query()
            ->where('project_name', $entity->name)
            ->whereBelongsTo($unit)
            ->get();

        if ($matchingOpportunities->count() > 1) {
            throw (new PipelinerSyncException("Multiple opportunities matched. Opportunity name [$entity->name], Unit [$unit->unit_name]."))->relatedTo(...$matchingOpportunities);
        }

        /** @var \App\Domain\Worldwide\Models\Opportunity $opportunity */
        $opportunity = $matchingOpportunities->first();

        if (null !== $opportunity?->pl_reference) {
            throw PipelinerSyncException::modelReferencesToDifferentEntity($opportunity)->relatedTo($opportunity);
        }

        return $opportunity;
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

    private function syncRelationsOfOpportunityEntity(OpportunityEntity $entity, Opportunity $model): void
    {
        $relations = await(parallel([
            'notes' => async(function () use ($entity): array {
                $iterator = $this->noteIntegration->scroll(filter: NoteFilterInput::new()->leadOpptyId(
                    EntityFilterStringField::eq($entity->id)
                ), first: 100);

                return iterator_to_array($iterator);
            }),
            'tasks' => async(function () use ($entity): array {
                $iterator = $this->taskIntegration->scroll(filter: TaskFilterInput::new()->opportunityRelations(
                    ActivityRelationFilterInput::new()->leadOpptyId(EntityFilterStringField::eq($entity->id))
                ), first: 100);

                return iterator_to_array($iterator);
            }),
            'appointments' => async(function () use ($entity): array {
                $iterator = $this->appointmentIntegration->scroll(filter: AppointmentFilterInput::new()
                    ->opportunityRelations(
                        ActivityRelationFilterInput::new()->leadOpptyId(EntityFilterStringField::eq($entity->id))
                    ), first: 100);

                return iterator_to_array($iterator);
            }),
        ]));

        $tasks = [
            function () use ($relations): void {
                foreach ($relations['notes'] as $item) {
                    $this->pullNoteStrategy->sync($item);
                }
            },
            function () use ($relations): void {
                foreach ($relations['tasks'] as $item) {
                    $this->pullTaskStrategy->sync($item);
                }
            },
            function () use ($relations): void {
                foreach ($relations['appointments'] as $item) {
                    $this->pullAppointmentStrategy->sync($item);
                }
            },
            function () use ($model, $entity): void {
                $attachments = collect($entity->documents)
                    ->lazy()
                    ->chunk(50)
                    ->map(function (LazyCollection $collection): array {
                        return $this->pullAttachmentStrategy->batch(...$collection->all());
                    })
                    ->collapse()
                    ->pipe(static function (LazyCollection $collection) {
                        return Collection::make($collection->all());
                    });

                if ($attachments->isNotEmpty()) {
                    $this->connection->transaction(
                        static fn () => $model->attachments()->syncWithoutDetaching($attachments)
                    );
                }
            },
        ];

        collect($tasks)->each(static function (callable $task): void {
            $task();
        });
    }

    public function syncByReference(string $reference): Model
    {
        return $this->sync(
            $this->oppIntegration->getById($reference)
        );
    }

    public function getModelType(): string
    {
        return (new Opportunity())->getMorphClass();
    }

    private function computeTotalEntitiesCountAndLastIdToPull(): array
    {
        $iterator = $this->oppIntegration->simpleScroll(
            ...$this->resolveScrollParameters(),
            ...['first' => 1_000]
        );

        $totalCount = 0;
        $lastId = null;

        while ($iterator->valid()) {
            ['id' => $lastId, 'modified' => $modified] = $iterator->current();

            if ($this->isStrategyYetToBeAppliedTo($lastId, $modified)) {
                ++$totalCount;
            }

            $iterator->next();
        }

        return [$totalCount, $lastId];
    }

    #[ArrayShape(['after' => 'string|null', 'filter' => OpportunityFilterInput::class])]
    private function resolveScrollParameters(): array
    {
        $filter = OpportunityFilterInput::new();
        $unitFilter = SalesUnitFilterInput::new()->name(EntityFilterStringField::eq(
            ...collect($this->getSalesUnits())->pluck('unit_name')
        ));
        $filter->unit($unitFilter);

        return [
            'after' => $this->getMostRecentScrollCursor()?->cursor,
            'filter' => $filter,
        ];
    }

    private function isStrategyYetToBeAppliedTo(string $plReference, string|\DateTimeInterface $modified): bool
    {
        $oppModel = new Opportunity();

        /** @var \App\Domain\Worldwide\Models\Opportunity|null $model */
        $model = $oppModel->newQuery()
            ->where('pl_reference', $plReference)
            ->withTrashed()
            ->select([$oppModel->getKeyName(), 'flags'])
            ->first();

        // Assume the strategy as not applied, if the model doesn't exist yet.
        if (null === $model) {
            return true;
        }

        if ($model->getFlag(Opportunity::SYNC_PROTECTED)) {
            return false;
        }

        $archivedUnresolvedErrorsExist = $model->syncErrors()
            ->whereNull('resolved_at')
            ->whereNotNull('archived_at')
            ->exists();

        if ($archivedUnresolvedErrorsExist) {
            return false;
        }

        $syncStrategyLogModel = new PipelinerSyncStrategyLog();

        return $syncStrategyLogModel->newQuery()
            ->whereMorphedTo('model', $model)
            ->where('strategy_name', (string) StrategyNameResolver::from($this))
            ->where($syncStrategyLogModel->getUpdatedAtColumn(), '>=', Carbon::parse($modified))
            ->doesntExist();
    }

    /**
     * @throws PipelinerSyncException
     */
    private function getMostRecentScrollCursor(): ?PipelinerModelScrollCursor
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    private function getBatchId(): ?string
    {
        return $this->options['batchId'] ?? null;
    }

    private function hasBatchId(): bool
    {
        return $this->getBatchId() !== null;
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Opportunity || $entity instanceof OpportunityEntity;
    }

    #[ArrayShape([
        'id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class,
    ])]
    public function getMetadata(?string $reference): array
    {
        $entity = $this->oppIntegration->getById($reference);

        return [
            'id' => $entity->id,
            'revision' => $entity->revision,
            'created' => $entity->created,
            'modified' => $entity->modified,
        ];
    }

    public function getByReference(string $reference): OpportunityEntity
    {
        return $this->oppIntegration->getById($reference);
    }
}
