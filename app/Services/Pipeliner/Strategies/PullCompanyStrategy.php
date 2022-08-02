<?php

namespace App\Services\Pipeliner\Strategies;

use App\Enum\Lock;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerNoteIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerPipelineIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerTaskIntegration;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\AccountFilterInput;
use App\Integrations\Pipeliner\Models\ActivityRelationFilterInput;
use App\Integrations\Pipeliner\Models\AppointmentEntity;
use App\Integrations\Pipeliner\Models\AppointmentFilterInput;
use App\Integrations\Pipeliner\Models\CloudObjectEntity;
use App\Integrations\Pipeliner\Models\ContactAccountRelationEntity;
use App\Integrations\Pipeliner\Models\ContactAccountRelationFilterInput;
use App\Integrations\Pipeliner\Models\ContactFilterInput;
use App\Integrations\Pipeliner\Models\ContactRelationEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\NoteEntity;
use App\Integrations\Pipeliner\Models\NoteFilterInput;
use App\Integrations\Pipeliner\Models\SalesUnitFilterInput;
use App\Integrations\Pipeliner\Models\TaskEntity;
use App\Integrations\Pipeliner\Models\TaskFilterInput;
use App\Models\Address;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ImportedAddress;
use App\Models\ImportedContact;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelScrollCursor;
use App\Services\Company\CompanyDataMapper;
use App\Services\Company\ImportedCompanyToPrimaryAccountProjector;
use App\Services\Opportunity\OpportunityEntityService;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use DateTimeInterface;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use JetBrains\PhpStorm\ArrayShape;

class PullCompanyStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface                      $connection,
                                protected PipelinerPipelineIntegration             $pipelineIntegration,
                                protected PipelinerAccountIntegration              $accountIntegration,
                                protected PipelinerContactIntegration              $contactIntegration,
                                protected PipelinerNoteIntegration                 $noteIntegration,
                                protected PipelinerAppointmentIntegration          $appointmentIntegration,
                                protected PipelinerTaskIntegration                 $taskIntegration,
                                protected PullNoteStrategy                         $pullNoteStrategy,
                                protected PullAppointmentStrategy                  $pullAppointmentStrategy,
                                protected PullAttachmentStrategy                   $pullAttachmentStrategy,
                                protected PullTaskStrategy                         $pullTaskStrategy,
                                protected OpportunityEntityService                 $entityService,
                                protected CompanyDataMapper                        $dataMapper,
                                protected ImportedCompanyToPrimaryAccountProjector $accountProjector,
                                protected LockProvider                             $lockProvider)
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
        $iterator = $this->accountIntegration->scroll(
            ...$this->resolveScrollParameters()
        );

        foreach ($iterator as $cursor => $item) {
            if ($this->isStrategyYetToBeAppliedTo($item->id, $item->modified)) {
                yield $cursor => $item;
            }
        }
    }

    #[ArrayShape(['after' => 'string|null', 'filter' => AccountFilterInput::class])]
    private function resolveScrollParameters(): array
    {
        $filter = AccountFilterInput::new();
        $unitFilter = SalesUnitFilterInput::new()->name(EntityFilterStringField::eq(
            ...collect($this->getSalesUnits())->pluck('unit_name')
        ));
        $filter->unit($unitFilter);

        return [
            'after' => $this->getMostRecentScrollCursor()?->cursor,
            'filter' => $filter,
        ];
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
                ->mapWithKeys(function (ContactAccountRelationEntity $relationEntity) use ($contact): array {
                    return [
                        $contact->id => new ContactRelationEntity(
                            id: $relationEntity->id,
                            isPrimary: $relationEntity->isPrimary,
                            contact: $contact,
                        ),
                    ];
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
    public function sync(object $entity, mixed ...$options): Model
    {
        if (!$entity instanceof AccountEntity) {
            throw new \TypeError(sprintf("Entity must be an instance of %s.", AccountEntity::class));
        }

        /** @var LazyCollection $accounts */
        $accounts = Company::query()
            ->withTrashed()
            ->where('pl_reference', $entity->id)
            ->lazyById(1);

        $contactRelations = collect($options['contactRelations'] ?? [])
            ->mapWithKeys(static function (ContactRelationEntity $entity): array {
                return [
                    $entity->contact->id => $entity,
                ];
            })
            ->all();

        if ($accounts->isEmpty()) {
            return $this->performSync($entity, contactRelations: $contactRelations);
        }

        $syncedAccount = null;

        foreach ($accounts as $account) {
            $syncedAccount = $this->performSync(entity: $entity, account: $account, contactRelations: $contactRelations);
        }

        return $syncedAccount;
    }

    private function performSync(AccountEntity $entity, Company $account = null, array $contactRelations = []): Model
    {
        $lock = $this->lockProvider->lock(Lock::SYNC_COMPANY($entity->id), 30);

        return $lock->block(30, function () use ($entity, $account, $contactRelations): Company {
            $contacts = [...$this->collectContactRelationsFromAccountEntity($entity), ...$contactRelations];

            $newAccount = $this->dataMapper->mapImportedCompanyFromAccountEntity($entity, $contacts);

            $this->connection->transaction(static function () use ($newAccount): void {
                $newAccount->addresses->each(static function (ImportedAddress $address) {
                    $address->owner?->save();
                    $address->save();
                });

                $newAccount->contacts->each(static function (ImportedContact $contact) {
                    $contact->owner?->save();
                    $contact->save();
                });

                $newAccount->save();

                $newAccount->addresses()->syncWithoutDetaching($newAccount->addresses);
                $newAccount->contacts()->syncWithoutDetaching($newAccount->contacts);
            });

            // Merge attributes when a model exists already.
            if (null !== $account) {
                $this->dataMapper->mergeAttributesFrom($account, $newAccount);

                $this->connection->transaction(static function () use ($account): void {
                    $account->addresses->each(static function (Address $address): void {
                        $address->user?->save();
                        $address->push();
                    });

                    $account->contacts->each(static function (Contact $contact): void {
                        $contact->user?->save();
                        $contact->push();
                    });

                    $account->withoutTimestamps(static function (Company $account): void {
                        $account->push();

                        $account->addresses()->sync($account->addresses);
                        $account->contacts()->sync($account->contacts);
                        $account->vendors()->sync($account->vendors);
                    });
                });

                $account->load(['addresses', 'contacts']);

                $this->syncRelationsOfAccountEntity($entity, $account);

                return $account;
            }

            $account = ($this->accountProjector)($newAccount);

            $this->syncRelationsOfAccountEntity($entity, $account);

            return $account;
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

    private function iterateRelationsOfOpportunityEntity(AccountEntity $entity): \Generator
    {
        $relations = [
            'notes' => fn() => $this->noteIntegration->scroll(filter: NoteFilterInput::new()->accountId(
                EntityFilterStringField::eq($entity->id)
            ), first: 100),
            'tasks' => fn() => $this->taskIntegration->scroll(filter: TaskFilterInput::new()->accountRelations(
                ActivityRelationFilterInput::new()->accountId(EntityFilterStringField::eq($entity->id))
            ), first: 100),
            'appointments' => fn() => $this->appointmentIntegration->scroll(filter: AppointmentFilterInput::new()->accountRelations(
                ActivityRelationFilterInput::new()->accountId(EntityFilterStringField::eq($entity->id))
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

    private function syncRelationsOfAccountEntity(AccountEntity $entity, Company $model): void
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

    private function computeTotalEntitiesCountAndLastIdToPull(): array
    {
        $iterator = $this->accountIntegration->simpleScroll(
            ...$this->resolveScrollParameters(),
            ...['first' => 1_000],
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
        $model = Company::query()
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
        return $entity instanceof Company;
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => DateTimeInterface::class,
        'modified' => DateTimeInterface::class])]
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