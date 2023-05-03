<?php

namespace App\Domain\Address\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class AddressOwnershipService implements ChangeOwnershipStrategy
{
    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Address) {
            throw new UnsupportedModelException();
        }

        $model->user()->associate($data->ownerId);

        if ($model->isClean()) {
            return;
        }

        $this->conResolver->connection()->transaction(static function () use ($model): void {
            $model->save();
        });
    }
}
