<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\Exceptions\EntityNotFoundException;
use App\Integrations\Pipeliner\GraphQl\PipelinerClientIntegration;
use App\Models\User;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;
use App\Services\Pipeliner\PipelinerClientLookupService;
use App\Services\Pipeliner\PipelinerSyncAggregate;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\User\UserDataMapper;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class PushClientStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(
        protected readonly ConnectionInterface $connection,
        protected readonly Cache $cache,
        protected readonly LockProvider $lockProvider,
        protected readonly PipelinerSyncAggregate $batch,
        protected readonly PipelinerClientLookupService $clientLookupService,
        protected readonly PipelinerClientIntegration $clientIntegration,
        protected readonly UserDataMapper $userDataMapper
    ) {
    }


    /**
     * @param  User  $model
     * @return void
     * @throws MultiplePipelinerEntitiesFoundException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof User) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", User::class));
        }

        $this->lockProvider->lock(static::class.$model->getKey(), 30)
            ->block(30, function () use ($model): void {
                $this->ensureReferenceIsValid($model);

                if (null !== $model->pl_reference) {
                    return;
                }

                // First, attempt to find the user.
                $clientEntity = $this->clientLookupService->find($model);

                if (null !== $clientEntity) {
                    tap($model, function (User $user) use ($clientEntity): void {
                        $user->pl_reference = $clientEntity->id;

                        $this->connection->transaction(static fn() => $user->saveQuietly());
                    });

                    return;
                }

                // When user not found, use the default client entity.
                $clientEntity = $this->clientLookupService->findDefaultEntity();

                tap($model, function (User $user) use ($clientEntity): void {
                    $user->pl_reference = $clientEntity->id;

                    $this->connection->transaction(static fn() => $user->saveQuietly());
                });
            });
    }

    protected function ensureReferenceIsValid(User $model): void
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
            $this->clientIntegration->getById($model->pl_reference);
        } catch (EntityNotFoundException) {
            $model->pl_reference = null;

            $model->saveQuietly();
        }
    }

    public function countPending(): int
    {
        return 0;
    }

    public function iteratePending(): \Traversable
    {
        return User::query()
            ->whereNull('pl_reference')
            ->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new User())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof User;
    }

    public function getByReference(string $reference): object
    {
        return User::query()->findOrFail($reference);
    }
}