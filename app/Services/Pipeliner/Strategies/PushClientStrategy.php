<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerClientIntegration;
use App\Models\User;
use App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException;
use App\Services\Pipeliner\PipelinerClientLookupService;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\User\UserDataMapper;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class PushClientStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface          $connection,
                                protected PipelinerClientLookupService $clientLookupService,
                                protected PipelinerClientIntegration   $clientIntegration,
                                protected UserDataMapper               $userDataMapper)
    {
    }


    /**
     * @param User $model
     * @return void
     * @throws MultiplePipelinerEntitiesFoundException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof User) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", User::class));
        }

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

//        $input = $this->userDataMapper->mapPipelinerCreateClientInput($model);

//        $clientEntity = $this->clientIntegration->create($input);

        tap($model, function (User $user) use ($clientEntity): void {
            $user->pl_reference = $clientEntity->id;

            $this->connection->transaction(static fn() => $user->saveQuietly());
        });
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