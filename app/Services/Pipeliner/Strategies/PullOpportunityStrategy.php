<?php

namespace App\Services\Pipeliner\Strategies;

use App\Enum\Lock;
use App\Events\Opportunity\OpportunityCreated;
use App\Events\Opportunity\OpportunityUpdated;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerNoteIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerPipelineIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerTaskIntegration;
use App\Integrations\Pipeliner\Models\ActivityRelationFilterInput;
use App\Integrations\Pipeliner\Models\AppointmentEntity;
use App\Integrations\Pipeliner\Models\AppointmentFilterInput;
use App\Integrations\Pipeliner\Models\CloudObjectEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\LeadOpptyAccountRelationEntity;
use App\Integrations\Pipeliner\Models\NoteEntity;
use App\Integrations\Pipeliner\Models\NoteFilterInput;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Integrations\Pipeliner\Models\SalesUnitFilterInput;
use App\Integrations\Pipeliner\Models\TaskEntity;
use App\Integrations\Pipeliner\Models\TaskFilterInput;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelScrollCursor;
use App\Services\Opportunity\OpportunityDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

class PullOpportunityStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface             $connection,
                                protected EventDispatcher                 $eventDispatcher,
                                protected PipelinerPipelineIntegration    $pipelineIntegration,
                                protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PullCompanyStrategy             $pullCompanyStrategy,
                                protected PullTaskStrategy                $pullTaskStrategy,
                                protected PullAppointmentStrategy         $pullAppointmentStrategy,
                                protected PullNoteStrategy                $pullNoteStrategy,
                                protected PullAttachmentStrategy          $pullAttachmentStrategy,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected PipelinerTaskIntegration        $taskIntegration,
                                protected PipelinerNoteIntegration        $noteIntegration,
                                protected OpportunityDataMapper           $oppDataMapper,
                                protected LockProvider                    $lockProvider)
    {
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
        $iterator = $this->oppIntegration->scroll(
            ...$this->resolveScrollParameters()
        );

        foreach ($iterator as $cursor => $item) {
            if ($this->isStrategyYetToBeAppliedTo($item->id, $item->modified)) {
                yield $cursor => $item;
            }
        }
    }

    /**
     * @param OpportunityEntity $entity
     * @return Opportunity
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof OpportunityEntity) {
            throw new \TypeError(sprintf("Entity must be an instance of %s.", OpportunityEntity::class));
        }

        $lock = $this->lockProvider->lock(Lock::SYNC_OPPORTUNITY($entity->id), 30);

        return $lock->block(30, function () use ($entity): Opportunity {
            /** @var Opportunity|null $opportunity */
            $opportunity = Opportunity::query()
                ->withTrashed()
                ->where('pl_reference', $entity->id)
                ->first();

            /** @var Collection|Company[] $accounts */
            $accounts = Collection::make($entity->accountRelations)
                ->map(function (LeadOpptyAccountRelationEntity $relationEntity) use ($entity): Company {
                    return $this->pullCompanyStrategy->sync($relationEntity->account, contactRelations: $relationEntity->isPrimary ? $entity->contactRelations : []);
                });

            $newOpportunity = $this->oppDataMapper->mapOpportunityFromOpportunityEntity($entity, $accounts);

            // Merge attributes when a model exists already.
            if (null !== $opportunity) {
                $oldOpportunity = (new Opportunity())->setRawAttributes($opportunity->getRawOriginal());

                $this->oppDataMapper->mergeAttributesFrom($opportunity, $newOpportunity);

                $this->connection->transaction(static function () use ($opportunity): void {
                    $opportunity->withoutTimestamps(static function (Opportunity $opportunity): void {
                        $opportunity->push();
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
            });

            $this->syncRelationsOfOpportunityEntity($entity, $newOpportunity);

            $this->eventDispatcher->dispatch(
                new OpportunityCreated($newOpportunity)
            );

            return $newOpportunity;
        });
    }

    private function iterateRelationsOfOpportunityEntity(OpportunityEntity $entity): \Generator
    {
        $relations = [
            'notes' => fn() => $this->noteIntegration->scroll(filter: NoteFilterInput::new()->leadOpptyId(
                EntityFilterStringField::eq($entity->id)
            ), first: 100),
            'tasks' => fn() => $this->taskIntegration->scroll(filter: TaskFilterInput::new()->opportunityRelations(
                ActivityRelationFilterInput::new()->leadOpptyId(EntityFilterStringField::eq($entity->id))
            ), first: 100),
            'appointments' => fn() => $this->appointmentIntegration->scroll(filter: AppointmentFilterInput::new()->opportunityRelations(
                ActivityRelationFilterInput::new()->leadOpptyId(EntityFilterStringField::eq($entity->id))
            ), first: 100),
        ];

        foreach ($relations as $relation => $callback) {
            try {
                foreach ($callback() as $item) {
                    yield $relation => $item;
                }
            } catch (GraphQlRequestException $e) {
                report($e);
            }
        }
    }

    private function syncRelationsOfOpportunityEntity(OpportunityEntity $entity, Opportunity $model): void
    {
        $relations = $this->iterateRelationsOfOpportunityEntity($entity);

        foreach ($relations as $relation => $item) {
            /** @var SyncStrategy $strategy */
            $strategy = match ($item::class) {
                NoteEntity::class => $this->pullNoteStrategy,
                TaskEntity::class => $this->pullTaskStrategy,
                AppointmentEntity::class => $this->pullAppointmentStrategy,
            };

            $strategy->sync($item);
        }

        $attachments = Collection::make($entity->documents)
            ->map(function (CloudObjectEntity $entity): Attachment {
                return $this->pullAttachmentStrategy->sync($entity);
            });

        $this->connection->transaction(static fn() => $model->attachments()->syncWithoutDetaching($attachments));
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
                $totalCount++;
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
        $model = Opportunity::query()
            ->where('pl_reference', $plReference)
            ->withTrashed()
            ->first();

        // Assume the strategy as not applied, if the model doesn't exist yet.
        if (null === $model) {
            return true;
        }

        $syncStrategyLogModel = new PipelinerSyncStrategyLog();

        return $syncStrategyLogModel->newQuery()
            ->whereMorphedTo('model', $model)
            ->where('strategy_name', (string)StrategyNameResolver::from($this))
            ->where($syncStrategyLogModel->getUpdatedAtColumn(), '>=', $modified)
            ->doesntExist();
    }

    /**
     * @throws PipelinerSyncException
     */
    private function getMostRecentScrollCursor(): ?PipelinerModelScrollCursor
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Opportunity;
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class])]
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
}