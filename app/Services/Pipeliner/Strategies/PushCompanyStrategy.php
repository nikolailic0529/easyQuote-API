<?php

namespace App\Services\Pipeliner\Strategies;

use App\Enum\AddressType;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\Models\ContactAccountRelationEntity;
use App\Integrations\Pipeliner\Models\ContactAccountRelationFilterInput;
use App\Integrations\Pipeliner\Models\ContactEntity;
use App\Integrations\Pipeliner\Models\ContactFilterInput;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelUpdateLog;
use App\Models\SalesUnit;
use App\Services\Company\CompanyDataMapper;
use App\Services\Pipeliner\PipelinerAccountLookupService;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\User\DefaultUserResolver;
use Generator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

class PushCompanyStrategy implements PushStrategy, ImpliesSyncOfHigherHierarchyEntities
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface           $connection,
                                protected PipelinerAccountLookupService $accountLookupService,
                                protected PipelinerAccountIntegration   $accountIntegration,
                                protected PipelinerContactIntegration   $contactIntegration,
                                protected CompanyDataMapper             $dataMapper,
                                protected DefaultUserResolver           $defaultUserResolver,
                                protected PushSalesUnitStrategy         $pushSalesUnitStrategy,
                                protected PushClientStrategy            $pushClientStrategy,
                                protected PushContactStrategy           $pushContactStrategy,
                                protected PushNoteStrategy              $pushNoteStrategy,
                                protected PushAttachmentStrategy        $pushAttachmentStrategy,
                                protected PushTaskStrategy              $pushTaskStrategy,
                                protected PushAppointmentStrategy       $pushAppointmentStrategy)
    {
    }

    private function modelsToBeUpdatedQuery(): Builder
    {
        $lastUpdatedAt = PipelinerModelUpdateLog::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Company();

        $syncStrategyLogModel = new PipelinerSyncStrategyLog();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->getQualifiedUpdatedAtColumn(),
            ])
            ->orderBy($model->getQualifiedUpdatedAtColumn())
            ->whereNonSystem()
            ->whereIn($model->salesUnit()->getQualifiedForeignKeyName(), Collection::make($this->getSalesUnits())->modelKeys())
            ->where(static function (Builder $builder) use ($model): void {
                $builder->whereColumn($model->getQualifiedUpdatedAtColumn(), '>', $model->getQualifiedCreatedAtColumn())
                    ->orWhereNull($model->qualifyColumn('pl_reference'));
            })
            ->leftJoinSub(
                $syncStrategyLogModel->newQuery()
                    ->selectRaw("max({$syncStrategyLogModel->getQualifiedUpdatedAtColumn()}) as {$syncStrategyLogModel->getUpdatedAtColumn()}")
                    ->addSelect($syncStrategyLogModel->model()->getQualifiedForeignKeyName())
                    ->where($syncStrategyLogModel->qualifyColumn('strategy_name'), (string)StrategyNameResolver::from($this))
                    ->groupBy($syncStrategyLogModel->model()->getQualifiedForeignKeyName())
                ,
                'latest_sync_strategy_log',
                "latest_sync_strategy_log.{$syncStrategyLogModel->model()->getForeignKeyName()}",
                $model->getQualifiedKeyName(),
            )
            ->where(static function (Builder $builder) use ($syncStrategyLogModel, $model): void {
                $builder
                    ->whereNull("latest_sync_strategy_log.{$syncStrategyLogModel->getUpdatedAtColumn()}")
                    ->orWhereColumn($model->getQualifiedUpdatedAtColumn(), '>', "latest_sync_strategy_log.{$syncStrategyLogModel->getUpdatedAtColumn()}");
            })
            ->unless(is_null($lastUpdatedAt), static function (Builder $builder) use ($model, $lastUpdatedAt): void {
                $builder->where($model->getQualifiedUpdatedAtColumn(), '>', $lastUpdatedAt);
            });
    }

    /**
     * @param Company $model
     * @return void
     */
    public function sync(Model $model, mixed ...$options): void
    {
        if (!$model instanceof Company) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", Company::class));
        }

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
            $accountEntity = $this->accountLookupService->find($model, $this->getSalesUnits());

            if (null !== $accountEntity) {
                tap($model, function (Company $company) use ($accountEntity): void {
                    $company->pl_reference = $accountEntity->id;

                    $this->connection->transaction(static fn() => $company->saveQuietly());
                });
            }
        }

        $this->syncAttachmentsFromAccount($model);

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateAccountInput($model);

            $accountEntity = $this->accountIntegration->create($input);

            tap($model, function (Company $company) use ($accountEntity): void {
                $company->pl_reference = $accountEntity->id;

                $this->connection->transaction(static fn() => $company->saveQuietly());
            });
        } else {
            $accountEntity = $this->accountIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateAccountInput($model, $accountEntity);

            $modifiedFields = $input->getModifiedFields();

            if (false === empty($modifiedFields)) {
                $this->accountIntegration->update($input);
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
    }

    private function syncNotesFromAccount(Company $model): void
    {
        foreach ($model->notes as $note) {
            $this->pushNoteStrategy->sync($note);
        }
    }

    private function syncAttachmentsFromAccount(Company $model): void
    {
        foreach ($model->attachments as $attachment) {
            $this->pushAttachmentStrategy->sync($attachment);
        }
    }

    private function syncTasksFromAccount(Company $model): void
    {
        foreach ($model->tasks as $task) {
            $this->pushTaskStrategy->sync($task);
        }
    }

    private function syncAppointmentsFromAccount(Company $model): void
    {
        foreach ($model->ownAppointments as $appointment) {
            $this->pushAppointmentStrategy->sync($appointment);
        }
    }

    private function syncContactRelationsFromAccount(Company $model): void
    {
        $sortedDefaultAddresses = $model->addresses->sortByDesc('pivot.is_default');

        $contactsToBeLinkedWithAddress = $model->contacts
            ->filter(static fn(Contact $contact): bool => null === $contact->address || $contact->address->address_type !== $contact->contact_type)
            ->each(static function (Contact $contact) use ($sortedDefaultAddresses): void {
                $address = $sortedDefaultAddresses
                    ->first(static fn(Address $address): bool => $contact->contact_type === $address->address_type);

                // Link with the first address of non Invoice type.
                $address ??= $sortedDefaultAddresses
                    ->first(static fn(Address $address): bool => $address->address_type !== AddressType::INVOICE);

                $contact->address()->associate($address);
            });

        $this->connection->transaction(static function () use ($contactsToBeLinkedWithAddress): void {
            $contactsToBeLinkedWithAddress->each->save();
        });

        $this->pushContactStrategy->setSalesUnits(...$this->getSalesUnits());

        $model->contacts
            ->each(function (Contact $contact): void {
                $this->pushContactStrategy->sync($contact);
            });

        // Refresh the contacts.
        $model->load(['addresses', 'contacts']);

        $input = $this->dataMapper->mapPipelinerCreateOrUpdateContactAccountRelationInputCollection($model);

        $this->accountIntegration->bulkUpdateContactAccountRelation($input);

        // Delete detached contact relations from account entity.
        $contactIdMap = collect($input)->keyBy('contactId');

        $iterator = $this->contactIntegration->scroll(
            filter: ContactFilterInput::new()->accountRelations(
                ContactAccountRelationFilterInput::new()->accountId(
                    EntityFilterStringField::eq($model->pl_reference)
                )
            )
        );

        /** @var string[] $detachedContactRelations */
        $detachedContactRelations = LazyCollection::make(static fn(): Generator => yield from $iterator)
            ->filter(static function (ContactEntity $contactEntity) use ($contactIdMap): bool {
                return $contactIdMap->has($contactEntity->id) === false;
            })
            ->reduce(static function (array $relations, ContactEntity $contactEntity) use ($model): array {
                return collect($contactEntity->accountRelations)
                    ->filter(static function (ContactAccountRelationEntity $entity) use ($model): bool {
                        return $entity->accountId === $model->pl_reference;
                    })
                    ->pluck('id')
                    ->merge($relations)
                    ->all();
            }, []);

        collect($detachedContactRelations)
            ->each(function (string $id): void {
                $this->accountIntegration->deleteContactAccountRelation($id);
            });
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
            ->lazyById()
            ->map(static function (Company $model): array {
                return [
                    'id' => $model->getKey(),
                    'modified' => $model->{$model->getUpdatedAtColumn()}
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
                $builder->whereIn((new Opportunity())->salesUnit()->getForeignKeyName(), Collection::make($this->salesUnits)->modelKeys());
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