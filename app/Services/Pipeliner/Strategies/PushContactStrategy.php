<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\Exceptions\EntityNotFoundException;
use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\Models\BulkUpdateResultMap;
use App\Integrations\Pipeliner\Models\ContactEntity;
use App\Integrations\Pipeliner\Models\CreateContactInput;
use App\Integrations\Pipeliner\Models\CreateOrUpdateContactInputCollection;
use App\Integrations\Pipeliner\Models\UpdateContactInput;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use App\Models\Contact;
use App\Services\Contact\ContactDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\PipelinerSyncBatch;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

class PushContactStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(
        protected readonly ConnectionInterface $connection,
        protected readonly Cache $cache,
        protected readonly LockProvider $lockProvider,
        protected readonly PipelinerSyncBatch $batch,
        protected readonly PipelinerContactIntegration $contactIntegration,
        protected readonly PushSalesUnitStrategy $pushSalesUnitStrategy,
        protected readonly PushClientStrategy $pushClientStrategy,
        protected readonly ContactDataMapper $dataMapper
    ) {
    }

    /**
     * @param  Contact  $model
     * @return void
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws PipelinerSyncException
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof Contact) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", Contact::class));
        }

        $this->lockProvider->lock(static::class.$model->getKey(), 30)
            ->block(30, function () use ($model): void {
                if (null !== $model->user) {
                    $this->pushClientStrategy->sync($model->user);
                }

                if (null !== $model->salesUnit) {
                    $this->pushSalesUnitStrategy->sync($model->salesUnit);
                }

                if (null === $model->address) {
                    throw PipelinerSyncException::undefinedContactAddressRelation($model);
                }

                $this->ensureReferenceIsValid($model);

                if (null === $model->pl_reference) {
                    $input = $this->dataMapper->mapPipelinerCreateContactInput($model);

                    $contactEntity = $this->contactIntegration->create($input,
                        ValidationLevelCollection::from(ValidationLevel::SKIP_ALL));

                    tap($model, function (Contact $contact) use ($contactEntity): void {
                        $contact->pl_reference = $contactEntity->id;

                        $this->connection->transaction(static fn() => $contact->saveQuietly());
                    });

                    return;
                }

                $contactEntity = $this->contactIntegration->getById($model->pl_reference);

                $input = $this->dataMapper->mapPipelinerUpdateContactInput($model, $contactEntity);

                if (count($input->getModifiedFields()) > 0) {
                    $this->contactIntegration->update($input,
                        ValidationLevelCollection::from(ValidationLevel::SKIP_ALL));
                }
            });
    }

    protected function ensureReferenceIsValid(Contact $model): void
    {
        if (null === $model->pl_reference) {
            return;
        }

        if ($this->batch->hasId()) {
            $key = static::class.$this->batch->id.':reference'.$model->pl_reference;

            if (!$this->cache->add($key, true)) {
                $model->pl_reference = $model->fresh()->pl_reference;

                $model->saveQuietly();

                return;
            }
        }

        try {
            $this->contactIntegration->getById($model->pl_reference);
        } catch (EntityNotFoundException) {
            $model->pl_reference = null;

            $model->saveQuietly();
        }
    }

    public function batch(Model $model, Model ...$models): void
    {
        $models = collect([$model, ...array_values($models)])->lazy();

        $models
            ->each(static function (Model $model): void {
                if (!$model instanceof Contact) {
                    throw new \TypeError(sprintf("Model must be an instance of %s.", Contact::class));
                }
            })
            ->each(function (Contact $contact): void {
                if (null === $contact->address) {
                    throw PipelinerSyncException::undefinedContactAddressRelation($contact);
                }

                if (null !== $contact->user) {
                    $this->pushClientStrategy->sync($contact->user);
                }

                if (null !== $contact->salesUnit) {
                    $this->pushSalesUnitStrategy->sync($contact->salesUnit);
                }
            });

        /** @var LazyCollection $linkedContacts */
        /** @var LazyCollection $unlinkedContacts */
        [$linkedContacts, $unlinkedContacts] = $models->partition(static function (Contact $contact): bool {
            return null !== $contact->pl_reference;
        })
            ->all();

        $contactEntityMap = collect();

        if ($linkedContacts->isNotEmpty()) {
            $contactEntities = collect($this->contactIntegration->getByIds(
                ...$linkedContacts->pluck('pl_reference')->all()
            ));

            $contactEntityMap = $contactEntities
                ->reject(static function (ContactEntity $entity): bool {
                    return $entity->isDeleted;
                })
                ->keyBy('id');

            $newlyUnlinkedContacts = $linkedContacts->reject(static function (Contact $contact) use ($contactEntityMap
            ) {
                return $contactEntityMap->has($contact->pl_reference);
            })
                ->each(static function (Contact $contact): void {
                    $contact->pl_reference = null;
                })
                ->values();

            $unlinkedContacts = $unlinkedContacts->merge($newlyUnlinkedContacts->all());
        }

        /** @var Collection $contacts */
        $contacts = $linkedContacts
            ->merge($unlinkedContacts)
            ->pipe(static function (LazyCollection $collection): Collection {
                return Collection::make($collection->all());
            });

        $input = $contacts
            ->lazy()
            ->map(function (Contact $contact) use ($contactEntityMap): CreateContactInput|UpdateContactInput {
                if (null === $contact->pl_reference) {
                    return $this->dataMapper->mapPipelinerCreateContactInput(
                        $contact,
                    );
                }

                return $this->dataMapper->mapPipelinerUpdateContactInput(
                    $contact,
                    $contactEntityMap->get($contact->pl_reference)
                );
            })
            ->pipe(static function (LazyCollection $collection): CreateOrUpdateContactInputCollection {
                return new CreateOrUpdateContactInputCollection(...$collection->all());
            });

        $results = $this->contactIntegration->bulkUpdate($input, ValidationLevelCollection::from(
            ValidationLevel::SKIP_ALL
        ));

        collect($results->created)
            ->each(function (BulkUpdateResultMap $resultMap) use ($contacts): void {
                /** @var Contact $contact */
                $contact = $contacts->get($resultMap->index);

                $contact->pl_reference = $resultMap->id;

                $this->connection->transaction(static fn() => $contact->save());
            });
    }

    public function countPending(): int
    {
        return 0;
    }

    public function iteratePending(): \Traversable
    {
        $model = new Contact();

        return $model->newQuery()
            ->whereIn($model->salesUnit()->getQualifiedForeignKeyName(),
                Collection::make($this->getSalesUnits())->modelKeys())
            ->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new Contact())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Contact;
    }

    public function getByReference(string $reference): object
    {
        return Contact::query()->findOrFail($reference);
    }
}