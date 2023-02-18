<?php

namespace App\Domain\Note\Services;

use App\Domain\Note\Models\Note;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class NoteOwnershipService implements ChangeOwnershipStrategy
{
    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Note) {
            throw new UnsupportedModelException();
        }

        $model->owner()->associate($data->ownerId);

        if ($model->isClean()) {
            return;
        }

        $this->conResolver->connection()->transaction(static function () use ($model): void {
            $model->save();
        });
    }
}
