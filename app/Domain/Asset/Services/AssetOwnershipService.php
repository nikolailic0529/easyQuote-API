<?php

namespace App\Domain\Asset\Services;

use App\Domain\Asset\Events\AssetOwnershipChanged;
use App\Domain\Asset\Models\Asset;
use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\Contracts\ProvidesLinkedModels;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class AssetOwnershipService implements ChangeOwnershipStrategy, ProvidesLinkedModels, CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Asset) {
            throw new UnsupportedModelException();
        }

        $oldModel = (new Asset())->setRawAttributes($model->getRawOriginal());

        $originalOwner = $model->user;
        $model->user()->associate($data->ownerId);

        if ($model->isClean()) {
            return;
        }

        $this->conResolver->connection()->transaction(static function () use ($originalOwner, $data, $model): void {
            $model->save();

            if ($data->keepOriginalOwnerEditor && $originalOwner && $originalOwner->isNot($model->user)) {
                $model->sharingUsers()->sync($originalOwner);
            } else {
                $model->sharingUsers()->detach();
            }
        });

        $this->eventDispatcher->dispatch(
            new AssetOwnershipChanged(
                asset: $model,
                oldAsset: $oldModel,
                causer: $this->causer,
            )
        );
    }

    public function getLinkedModels(Model $model): iterable
    {
        if (!$model instanceof Asset) {
            return;
        }

        yield $model->address;
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn(): ?Model => $this->causer = $causer);
    }
}
