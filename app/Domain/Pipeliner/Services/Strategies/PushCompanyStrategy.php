<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Address\Enum\AddressType;
use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Services\CompanyDataMapper;
use App\Domain\Company\Services\Exceptions\CompanyDataMappingException;
use App\Domain\Contact\Models\Contact;
use App\Domain\Note\Models\Note;
use App\Domain\Pipeliner\Events\SyncStrategyPerformed;
use App\Domain\Pipeliner\Integration\Enum\ValidationLevel;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountSharingClientRelationIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerContactIntegration;
use App\Domain\Pipeliner\Integration\Models\AccountEntity;
use App\Domain\Pipeliner\Integration\Models\AccountSharingClientRelationEntity;
use App\Domain\Pipeliner\Integration\Models\AccountSharingClientRelationFilterInput;
use App\Domain\Pipeliner\Integration\Models\ContactAccountRelationEntity;
use App\Domain\Pipeliner\Integration\Models\ContactAccountRelationFilterInput;
use App\Domain\Pipeliner\Integration\Models\ContactEntity;
use App\Domain\Pipeliner\Integration\Models\ContactFilterInput;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\ValidationLevelCollection;
use App\Domain\Pipeliner\Models\PipelinerModelUpdateLog;
use App\Domain\Pipeliner\Models\PipelinerSyncStrategyLog;
use App\Domain\Pipeliner\Services\Exceptions\PipelinerSyncException;
use App\Domain\Pipeliner\Services\PipelinerAccountLookupService;
use App\Domain\Pipeliner\Services\PipelinerSyncAggregate;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PushStrategy;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Sync\Enum\Lock;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\User\Services\ApplicationUserResolver;
use App\Domain\Worldwide\Models\Opportunity;
use Clue\React\Mq\Queue;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use function React\Async\async;
use function React\Async\await;

class PushCompanyStrategy implements PushStrategy, ImpliesSyncOfHigherHierarchyEntities
{
    use SalesUnitsAware;

    public function __construct(
        protected ConnectionInterface $connection,
        protected PipelinerAccountLookupService $accountLookupService,
        protected PipelinerAccountIntegration $accountIntegration,
        protected PipelinerAccountSharingClientRelationIntegration $sharingClientRelationIntegration,
        protected PipelinerContactIntegration $contactIntegration,
        protected CompanyDataMapper $dataMapper,
        protected ApplicationUserResolver $defaultUserResolver,
        protected PushSalesUnitStrategy $pushSalesUnitStrategy,
        protected PushClientStrategy $pushClientStrategy,
        protected PushContactStrategy $pushContactStrategy,
        protected PushNoteStrategy $pushNoteStrategy,
        protected PushAttachmentStrategy $pushAttachmentStrategy,
        protected PushTaskStrategy $pushTaskStrategy,
        protected PushAppointmentStrategy $pushAppointmentStrategy,
        protected LockProvider $lockProvider,
        protected EventDispatcher $eventDispatcher,
        protected PipelinerSyncAggregate $syncAggregate,
    ) {
    }

    private function modelsToBeUpdatedQuery(): Builder
    {
        $lastUpdatedAt = PipelinerModelUpdateLog::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Company();
        $salesUnitModel = new SalesUnit();

        $syncStrategyLogModel = new PipelinerSyncStrategyLog();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->getQualifiedUpdatedAtColumn(),
                $model->qualifyColumn('pl_reference'),
                $model->qualifyColumn('name'),
                $salesUnitModel->qualifyColumn('unit_name'),
            ])
            ->orderBy($model->getQualifiedUpdatedAtColumn())
            ->whereNonSystem()
            ->join($salesUnitModel->getTable(), $model->salesUnit()->getQualifiedForeignKeyName(),
                $salesUnitModel->getQualifiedKeyName())
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
            ->where(static function (Builder $builder): void {
                $builder->has('opportunities')
                    ->orHas('opportunitiesWhereEndUser');
            })
            ->unless(is_null($lastUpdatedAt), static function (Builder $builder) use ($model, $lastUpdatedAt): void {
                $builder->where($model->getQualifiedUpdatedAtColumn(), '>', $lastUpdatedAt);
            });
    }

    /**
     * @param Company $model
     */
    public function sync(Model $model, mixed ...$options): void
    {
        if (!$model instanceof Company) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', Company::class));
        }

        if ($model->getFlag(Company::SYNC_PROTECTED)) {
            throw PipelinerSyncException::modelProtectedFromSync($model)->relatedTo($model);
        }

        if (null === $model->salesUnit) {
            throw PipelinerSyncException::modelDoesntHaveUnitRelation($model)->relatedTo($model);
        }

        if (!$model->salesUnit->is_enabled) {
            throw PipelinerSyncException::modelBelongsToDisabledUnit($model, $model->salesUnit)->relatedTo($model);
        }

        $lock = $this->lockProvider->lock(Lock::SYNC_COMPANY($model->getKey()), 120);

        $lock->block(120, function () use ($model): void {
            $model->refresh();

            if (null === $model->owner) {
                $model->owner()->associate($this->defaultUserResolver->resolve());
            }

            if (null !== $model->owner) {
                $this->pushClientStrategy->sync($model->owner);
            }

            if (null !== $model->salesUnit) {
                $this->pushSalesUnitStrategy->sync($model->salesUnit);
            }

            if (null === $model->pl_reference) {
                $accountEntity = $this->accountLookupService->find($model, [$model->salesUnit]);

                if (null !== $accountEntity) {
                    tap($model, function (Company $company) use ($accountEntity): void {
                        $company->pl_reference = $accountEntity->id;

                        $this->connection->transaction(static fn () => $company->saveQuietly());
                    });
                }
            }

            $this->syncAttachmentsFromAccount($model);
            $this->syncSharingUsersFromAccount($model);

            if (null === $model->pl_reference) {
                try {
                    $input = $this->dataMapper->mapPipelinerCreateAccountInput($model);
                } catch (CompanyDataMappingException $e) {
                    throw new PipelinerSyncException(message: $e->getMessage(), previous: $e);
                }

                $accountEntity = $this->accountIntegration->create($input,
                    validationLevel: ValidationLevelCollection::from(ValidationLevel::SKIP_ALL));

                tap($model, function (Company $company) use ($accountEntity): void {
                    $company->pl_reference = $accountEntity->id;

                    $this->connection->transaction(static fn () => $company->saveQuietly());
                });
            } else {
                $accountEntity = $this->accountIntegration->getById($model->pl_reference);
                $sharingClients = $this->collectSharingClientRelationsFromAccountEntity($accountEntity);

                try {
                    $input = $this->dataMapper->mapPipelinerUpdateAccountInput($model, $accountEntity, $sharingClients);
                } catch (CompanyDataMappingException $e) {
                    throw new PipelinerSyncException(message: $e->getMessage(), previous: $e);
                }

                $modifiedFields = $input->getModifiedFields();

                if (false === empty($modifiedFields)) {
                    $this->accountIntegration->update($input,
                        validationLevel: ValidationLevelCollection::from(ValidationLevel::SKIP_ALL));
                }
            }

            $tasks = [
                fn () => $this->syncContactRelationsFromAccount($model),
                fn () => $this->syncNotesFromAccount($model),
                fn () => $this->syncTasksFromAccount($model),
                fn () => $this->syncAppointmentsFromAccount($model),
            ];

            collect($tasks)->each(static function (callable $task): void {
                $task();
            });
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
        tap(new PipelinerSyncStrategyLog(), function (PipelinerSyncStrategyLog $log) use ($model): void {
            $log->model()->associate($model);
            $log->strategy_name = (string) StrategyNameResolver::from($this);
            $log->save();
        });
    }

    private function syncNotesFromAccount(Company $model): void
    {
        $queue = Queue::all(concurrency: 10, jobs: $model->notes->all(), handler: async(function (Note $model): void {
            $this->pushNoteStrategy->sync($model);
        }));

        await($queue);
    }

    private function syncAttachmentsFromAccount(Company $model): void
    {
        $queue = Queue::all(concurrency: 10, jobs: $model->attachments->all(), handler: async(function (
            Attachment $model
        ): void {
            $this->pushAttachmentStrategy->sync($model);
        }));

        await($queue);
    }

    private function syncSharingUsersFromAccount(Company $model): void
    {
        $queue = Queue::all(concurrency: 10, jobs: $model->sharingUsers->all(), handler: async(function (
            User $model
        ): void {
            $this->pushClientStrategy->sync($model);
        }));

        await($queue);
    }

    private function syncTasksFromAccount(Company $model): void
    {
        $queue = Queue::all(concurrency: 5, jobs: $model->tasks->all(), handler: async(function (Task $model): void {
            $this->pushTaskStrategy->sync($model);
        }));

        await($queue);
    }

    private function syncAppointmentsFromAccount(Company $model): void
    {
        $queue = Queue::all(concurrency: 10, jobs: $model->ownAppointments->all(), handler: async(function (
            Appointment $model
        ): void {
            $this->pushAppointmentStrategy->sync($model);
        }));

        await($queue);
    }

    /**
     * @return list<AccountSharingClientRelationEntity>
     */
    private function collectSharingClientRelationsFromAccountEntity(AccountEntity $entity): array
    {
        $iterator = $this->sharingClientRelationIntegration->scroll(
            filter: AccountSharingClientRelationFilterInput::new()
                ->accountId(
                    EntityFilterStringField::eq($entity->id)
                )
        );

        return LazyCollection::make(static function () use ($iterator): \Generator {
            yield from $iterator;
        })
            ->values()
            ->all();
    }

    private function syncContactRelationsFromAccount(Company $model): void
    {
        $sortedDefaultAddresses = $model->addresses->sortByDesc('pivot.is_default');
        $invoiceAddress = $sortedDefaultAddresses
            ->first(static fn (Address $address): bool => $address->address_type === AddressType::INVOICE);

        $contactsToBeLinkedWithAddress = $model->contacts
            ->filter(static fn (Contact $contact): bool => null === $contact->address)
            ->each(static function (Contact $contact) use ($invoiceAddress): void {
                $contact->address()->associate($invoiceAddress);
            });

        $this->connection->transaction(static function () use ($contactsToBeLinkedWithAddress): void {
            $contactsToBeLinkedWithAddress->each->save();
        });

        $this->pushContactStrategy->setSalesUnits(...$this->getSalesUnits());

        if ($model->contacts->isNotEmpty()) {
            $this->pushContactStrategy->batch(...$model->contacts);
        }

        // Refresh the contacts.
        $model->load(['addresses', 'contacts']);

        $input = $this->dataMapper->mapPipelinerCreateOrUpdateContactAccountRelationInputCollection($model);

        $this->accountIntegration->bulkUpdateContactAccountRelation($input);

        $currentAccountContacts = $this->contactIntegration->getByCriteria(
            ContactFilterInput::new()->accountRelations(
                ContactAccountRelationFilterInput::new()
                    ->accountId(EntityFilterStringField::eq($model->pl_reference))
            )
        );

        $contactRefMap = $model->contacts
            ->lazy()
            ->keyBy('pl_reference')
            ->map(static fn (): bool => true)
            ->collect();

        $contactAccRelationsToBeDeleted = collect($currentAccountContacts)
            ->lazy()
            ->reject(static function (ContactEntity $entity) use ($contactRefMap): bool {
                return $contactRefMap->has($entity->id);
            })
            ->map(static function (ContactEntity $entity) use ($model): array {
                return collect($entity->accountRelations)
                    ->lazy()
                    ->filter(static function (ContactAccountRelationEntity $rel) use ($model): bool {
                        return $rel->accountId === $model->pl_reference;
                    })
                    ->map(static function (ContactAccountRelationEntity $rel) {
                        return $rel->id;
                    })
                    ->values()
                    ->all();
            })
            ->collapse()
            ->unique()
            ->values()
            ->collect();

        if ($contactAccRelationsToBeDeleted->isNotEmpty()) {
            $queue = Queue::all(concurrency: 5, jobs: $contactAccRelationsToBeDeleted->all(), handler: async(function (string $relId): void {
                $this->accountIntegration->deleteContactAccountRelation($relId);
            }));

            await($queue);
        }
    }

    public function setSalesUnits(SalesUnit ...$units): static
    {
        return tap($this, fn () => $this->salesUnits = $units);
    }

    public function getSalesUnits(): array
    {
        return $this->salesUnits;
    }

    public function countPending(): int
    {
        return $this->modelsToBeUpdatedQuery()->count();
    }

    public function iteratePending(): \Traversable
    {
        return $this->modelsToBeUpdatedQuery()
            ->lazyById(column: (new Company())->getQualifiedKeyName())
            ->map(static function (Company $model): array {
                return [
                    'id' => $model->getKey(),
                    'pl_reference' => $model->pl_reference,
                    'modified' => $model->{$model->getUpdatedAtColumn()}?->toIso8601String(),
                    'name' => $model->project_name,
                    'unit_name' => $model->unit_name,
                ];
            });
    }

    public function getModelType(): string
    {
        return (new Company())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Company;
    }

    /**
     * @param mixed|Company $entity
     */
    public function resolveHigherHierarchyEntities(mixed $entity): LazyCollection
    {
        if (!$entity instanceof Company) {
            throw new \TypeError(sprintf('Entity must be an instance of %s.', Company::class));
        }

        return LazyCollection::make(function () use ($entity): \Generator {
            $where = function (Builder $builder): void {
                $builder->whereIn((new Opportunity())->salesUnit()->getForeignKeyName(),
                    Collection::make($this->salesUnits)->modelKeys());
            };

            yield from $entity->opportunities()
                ->tap($where)
                ->lazy(100);

            yield from $entity->opportunitiesWhereEndUser()
                ->tap($where)
                ->lazy(100);
        })
            ->unique(static function (Opportunity $opportunity): string {
                return $opportunity->getKey();
            })
            ->filter(function (Opportunity $opportunity): bool {
                foreach ($this->salesUnits as $unit) {
                    if ($opportunity->salesUnit()->is($unit)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    public function getByReference(string $reference): object
    {
        return Company::query()->findOrFail($reference);
    }
}
