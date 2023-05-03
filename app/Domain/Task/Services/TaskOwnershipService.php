<?php

namespace App\Domain\Task\Services;

use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use App\Domain\Task\Models\Task;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class TaskOwnershipService implements ChangeOwnershipStrategy
{
    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Task) {
            throw new UnsupportedModelException();
        }

        $model->user()->associate($data->ownerId);
        if ($data->salesUnitId) {
            $model->salesUnit()->associate($data->salesUnitId);
        }

        if ($model->isClean()) {
            return;
        }

        $this->conResolver->connection()->transaction(static function () use ($model): void {
            $model->save();
        });
    }
}
