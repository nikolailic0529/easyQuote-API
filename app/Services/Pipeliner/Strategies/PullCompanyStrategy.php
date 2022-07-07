<?php

namespace App\Services\Pipeliner\Strategies;

use App\Enum\Lock;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerPipelineIntegration;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\ContactAccountRelationEntity;
use App\Integrations\Pipeliner\Models\ContactAccountRelationFilterInput;
use App\Integrations\Pipeliner\Models\ContactFilterInput;
use App\Integrations\Pipeliner\Models\ContactRelationEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\PipelineEntity;
use App\Models\Company;
use App\Models\Pipeline\Pipeline;
use App\Models\PipelinerModelScrollCursor;
use App\Services\Company\CompanyDataMapper;
use App\Services\Company\ImportedCompanyToPrimaryAccountProjector;
use App\Services\Opportunity\OpportunityEntityService;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use DateTimeInterface;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use JetBrains\PhpStorm\ArrayShape;

class PullCompanyStrategy implements PullStrategy
{
    protected ?Pipeline $pipeline = null;
    protected ?PipelineEntity $pipelineEntity = null;

    public function __construct(protected ConnectionInterface                      $connection,
                                protected PipelinerPipelineIntegration             $pipelineIntegration,
                                protected PipelinerAccountIntegration              $accountIntegration,
                                protected PipelinerContactIntegration              $contactIntegration,
                                protected OpportunityEntityService                 $entityService,
                                protected CompanyDataMapper                        $dataMapper,
                                protected ImportedCompanyToPrimaryAccountProjector $accountProjector,
                                protected LockProvider                             $lockProvider)
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

        $cursor = $this->getMostRecentScrollCursor();

        return $this->accountIntegration->scroll(
            after: $cursor?->cursor,
        );
    }

    private function collectContactRelationsFromAccountEntity(AccountEntity $entity): array
    {
        $iterator = $this->contactIntegration->scroll(
            filter: ContactFilterInput::new()->accountRelations(
                ContactAccountRelationFilterInput::new()->accountId(
                    EntityFilterStringField::eq($entity->id)
                )
            )
        );

        $contacts = [];

        foreach ($iterator as $cursor => $contact) {
            $contactRelations = collect($contact->accountRelations)
                ->where('accountId', $entity->id)
                ->map(function (ContactAccountRelationEntity $relationEntity) use ($contact): ContactRelationEntity {
                    return new ContactRelationEntity(
                        id: $relationEntity->id,
                        isPrimary: $relationEntity->isPrimary,
                        contact: $contact,
                    );
                })
                ->all();

            $contacts = array_merge($contacts, $contactRelations);
        }

        return $contacts;
    }

    /**
     * @param AccountEntity $entity
     * @return Company
     */
    public function sync(object $entity): Model
    {
        /** @var LazyCollection $accounts */
        $accounts = Company::query()
            ->withTrashed()
            ->where('pl_reference', $entity->id)
            ->lazyById(1);

        if ($accounts->isEmpty()) {
            return $this->performSync($entity);
        }

        $syncedAccount = null;

        foreach ($accounts as $account) {
            $syncedAccount = $this->performSync($entity, $account);
        }

        return $syncedAccount;
    }

    private function performSync(AccountEntity $entity, Company $account = null): Model
    {
        $lock = $this->lockProvider->lock(Lock::SYNC_COMPANY($entity->id), 30);

        return $lock->block(30, function () use ($entity, $account): Company {
            $contacts = $this->collectContactRelationsFromAccountEntity($entity);

            // Merge attributes when a model exists already.
            if (null !== $account) {
                $updatedAccount = $this->dataMapper->mapImportedCompanyFromAccountEntity($entity, $contacts);

                $this->dataMapper->mergeAttributesFrom($account, $updatedAccount);

                $this->connection->transaction(static function () use ($account): void {
                    $account->addresses->each->save();
                    $account->contacts->each->save();

                    $account->withoutTimestamps(static function (Company $company) use ($account): void {
                        $account->push();

                        $account->addresses()->sync($account->addresses);
                        $account->contacts()->sync($account->contacts);
                    });
                });

                return $account;
            }

            $importedAccount = $this->dataMapper->mapImportedCompanyFromAccountEntity($entity, $contacts);

            return ($this->accountProjector)($importedAccount);
        });
    }

    public function syncByReference(string $reference): Model
    {
        return $this->sync(
            $this->accountIntegration->getById($reference)
        );
    }

    public function getModelType(): string
    {
        return (new Company())->getMorphClass();
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
        $iterator = $this->accountIntegration->simpleScroll(
            after: $scrollCursor?->cursor,
            chunkSize: 1000
        );

        $totalCount = 0;
        $lastId = null;

        while ($iterator->valid()) {
            $lastId = $iterator->current();
            $totalCount++;

            $iterator->next();
        }

        return [$totalCount, $lastId];
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
        return $entity instanceof Company;
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => DateTimeInterface::class, 'modified' => DateTimeInterface::class])]
    public function getMetadata(string $reference): array
    {
        $entity = $this->accountIntegration->getById($reference);

        return [
            'id' => $entity->id,
            'revision' => $entity->revision,
            'created' => $entity->created,
            'modified' => $entity->modified,
        ];
    }
}