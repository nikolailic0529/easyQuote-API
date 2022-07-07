<?php

namespace App\Services\Pipeliner\Strategies;

use App\Enum\Lock;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerNoteIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerPipelineIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerTaskIntegration;
use App\Integrations\Pipeliner\Models\ActivityRelationFilterInput;
use App\Integrations\Pipeliner\Models\AppointmentEntity;
use App\Integrations\Pipeliner\Models\AppointmentFilterInput;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\NoteEntity;
use App\Integrations\Pipeliner\Models\NoteFilterInput;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Integrations\Pipeliner\Models\PipelineEntity;
use App\Integrations\Pipeliner\Models\TaskEntity;
use App\Integrations\Pipeliner\Models\TaskFilterInput;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ImportedAddress;
use App\Models\ImportedCompany;
use App\Models\ImportedContact;
use App\Models\Opportunity;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelScrollCursor;
use App\Services\Company\CompanyDataMapper;
use App\Services\Opportunity\OpportunityDataMapper;
use App\Services\Opportunity\OpportunityEntityService;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

class PullOpportunityStrategy implements PullStrategy
{
    protected ?Pipeline $pipeline = null;
    protected ?PipelineEntity $pipelineEntity = null;

    public function __construct(protected ConnectionInterface             $connection,
                                protected PipelinerPipelineIntegration    $pipelineIntegration,
                                protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PullTaskStrategy                $pullTaskStrategy,
                                protected PullAppointmentStrategy         $pullAppointmentStrategy,
                                protected PullNoteStrategy                $pullNoteStrategy,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected PipelinerTaskIntegration        $taskIntegration,
                                protected PipelinerNoteIntegration        $noteIntegration,
                                protected OpportunityEntityService        $entityService,
                                protected CompanyDataMapper               $companyDataMapper,
                                protected OpportunityDataMapper           $oppDataMapper,
                                protected LockProvider                    $lockProvider)
    {
    }

    public function setPipeline(Pipeline $pipeline): static
    {
        return tap($this, function () use ($pipeline): void {
            $this->pipeline = $pipeline;
            $this->pipelineEntity = null;
        });
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    /**
     * @throws PipelinerSyncException
     */
    public function countPending(): int
    {
        $this->pipeline ?? throw PipelinerSyncException::unsetPipeline();

        [$count, $lastId] = $this->computeTotalEntitiesCountAndLastIdToPull($this->getMostRecentScrollCursor());

        return $count;
    }

    public function iteratePending(): \Traversable
    {
        $this->pipeline ?? throw PipelinerSyncException::unsetPipeline();

        $pipelineEntity = $this->resolvePipelineEntity();

        $cursor = $this->getMostRecentScrollCursor();

        $iterator = $this->oppIntegration->scroll(
            after: $cursor?->cursor,
            filter: OpportunityFilterInput::new()->pipelineId(EntityFilterStringField::eq($pipelineEntity->id))
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
        $lock = $this->lockProvider->lock(Lock::SYNC_OPPORTUNITY($entity->id), 30);

        return $lock->block(30, function () use ($entity): Opportunity {
            /** @var Opportunity|null $opportunity */
            $opportunity = Opportunity::query()
                ->withTrashed()
                ->where('pl_reference', $entity->id)
                ->first();

            $primaryAccount = $this->companyDataMapper->mapImportedCompanyFromAccountEntity($entity->primaryAccount, $entity->contactRelations);

            // Merge attributes when a model exists already.
            if (null !== $opportunity) {
                $updatedOpportunity = $this->oppDataMapper->mapOpportunityFromOpportunityEntity($entity, $primaryAccount);

                $this->oppDataMapper->mergeAttributesFrom($opportunity, $updatedOpportunity);

                $this->saveImportedPrimaryAccount($opportunity->importedPrimaryAccount);

                $primaryAcc = $this->entityService->associateOpportunityWithImportedPrimaryAccount($opportunity);

                $this->entityService->associateOpportunityWithImportedPrimaryAccountContact($opportunity, $primaryAcc);

                $this->connection->transaction(static function () use ($opportunity): void {
                    $opportunity->withoutTimestamps(static function (Opportunity $opportunity): void {
                        $opportunity->primaryAccount?->withoutTimestamps(static fn(Company $company): bool => $company->save());
                        $opportunity->endUser?->withoutTimestamps(static fn(Company $company): bool => $company->save());

                        $opportunity->push();
                    });
                });

                $this->syncRelationsOfOpportunityEntity($entity);

                return $opportunity;
            }

            $opportunity = $this->oppDataMapper->mapOpportunityFromOpportunityEntity($entity, $primaryAccount);

            $this->saveImportedPrimaryAccount($opportunity->importedPrimaryAccount);

            $this->connection->transaction(static function () use ($opportunity) {
                $opportunity->push();
            });

            $this->entityService->finishSavingOfImportedOpportunity($opportunity);

            $this->syncRelationsOfOpportunityEntity($entity);

            return $opportunity;
        });
    }

    private function saveImportedPrimaryAccount(ImportedCompany $primaryAccount): void
    {
        $this->connection->transaction(static function () use ($primaryAccount): void {
            $primaryAccount->contacts->each(static function (ImportedContact $contact): void {
                $contact->owner?->save();
                $contact->save();
            });

            $primaryAccount->addresses->each(static function (ImportedAddress $address): void {
                $address->owner?->save();
                $address->save();
            });

            $primaryAccount->push();

            $primaryAccount->addresses()->sync($primaryAccount->addresses);
            $primaryAccount->contacts()->sync($primaryAccount->contacts);
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

    private function syncRelationsOfOpportunityEntity(OpportunityEntity $entity): void
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

    private function resolvePipelineEntity(): PipelineEntity
    {
        return $this->pipelineEntity ??= collect($this->pipelineIntegration->getAll())
            ->sole(function (PipelineEntity $entity): bool {
                return 0 === strcasecmp($entity->name, $this->pipeline->pipeline_name);
            });
    }

    private function computeTotalEntitiesCountAndLastIdToPull(PipelinerModelScrollCursor $scrollCursor = null): array
    {
        $pipelineEntity = $this->resolvePipelineEntity();

        $iterator = $this->oppIntegration->simpleScroll(
            after: $scrollCursor?->cursor,
            filter: OpportunityFilterInput::new()->pipelineId(EntityFilterStringField::eq($pipelineEntity->id)),
            chunkSize: 1000
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
        $this->pipeline ?? throw PipelinerSyncException::unsetPipeline();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->whereBelongsTo($this->pipeline)
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Opportunity;
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class, 'modified' => \DateTimeInterface::class])]
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