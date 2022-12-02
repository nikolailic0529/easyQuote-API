<?php

namespace App\Services\Pipeliner\Strategies;

use App\Enum\AddressType;
use App\Enum\Lock;
use App\Events\Pipeliner\SyncStrategyPerformed;
use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use App\Models\Address;
use App\Models\Appointment\Appointment;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelUpdateLog;
use App\Models\SalesUnit;
use App\Models\Task\Task;
use App\Services\Company\CompanyDataMapper;
use App\Services\Company\Exceptions\CompanyDataMappingException;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\PipelinerAccountLookupService;
use App\Services\Pipeliner\PipelinerSyncAggregate;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\User\ApplicationUserResolver;
use Clue\React\Mq\Queue;
use Generator;
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
                    ->groupBy($syncStrategyLogModel->model()->getQualifiedForeignKeyName())
                ,
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
     * @param  Company  $model
     * @return void
     */
    public function sync(Model $model, mixed ...$options): void
    {
        if (!$model instanceof Company) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", Company::class));
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

                        $this->connection->transaction(static fn() => $company->saveQuietly());
                    });
                }
            }

            $this->syncAttachmentsFromAccount($model);

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

                    $this->connection->transaction(static fn() => $company->saveQuietly());
                });
            } else {
                $accountEntity = $this->accountIntegration->getById($model->pl_reference);

                try {
                    $input = $this->dataMapper->mapPipelinerUpdateAccountInput($model, $accountEntity);
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
                fn() => $this->syncContactRelationsFromAccount($model),
                fn() => $this->syncNotesFromAccount($model),
                fn() => $this->syncTasksFromAccount($model),
                fn() => $this->syncAppointmentsFromAccount($model),
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
        tap(new PipelinerSyncStrategyLog(), function (PipelinerSyncStrategyLog $log) use ($model) {
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

    private function syncContactRelationsFromAccount(Company $model): void
    {
        $sortedDefaultAddresses = $model->addresses->sortByDesc('pivot.is_default');
        $invoiceAddress = $sortedDefaultAddresses
            ->first(static fn(Address $address): bool => $address->address_type === AddressType::INVOICE);

        $contactsToBeLinkedWithAddress = $model->contacts
            ->filter(static fn(Contact $contact): bool => null === $contact->address)
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
    }

    public function setSalesUnits(SalesUnit ...$units): static
    {
        return tap($this, fn() => $this->salesUnits = $units);
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
     * @param  mixed|Company  $entity
     * @return LazyCollection
     */
    public function resolveHigherHierarchyEntities(mixed $entity): LazyCollection
    {
        if (!$entity instanceof Company) {
            throw new \TypeError(sprintf("Entity must be an instance of %s.", Company::class));
        }

        return LazyCollection::make(function () use ($entity): Generator {
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