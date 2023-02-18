<?php

namespace App\Domain\Contact\Services;

use App\Domain\Contact\Models\Contact;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class ContactOwnershipService implements ChangeOwnershipStrategy
{
    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Contact) {
            throw new UnsupportedModelException();
        }

        $model->user()->associate($data->ownerId);
        $model->salesUnit()->associate($data->salesUnitId);

        if ($model->isClean()) {
            return;
        }

        $this->conResolver->connection()->transaction(static function () use ($model): void {
            $model->save();
        });
    }
}
