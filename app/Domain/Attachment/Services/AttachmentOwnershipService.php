<?php

namespace App\Domain\Attachment\Services;

use App\Domain\Attachment\Events\AttachmentOwnershipChanged;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class AttachmentOwnershipService implements ChangeOwnershipStrategy, CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly AttachmentDataMapper $mapper,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Attachment) {
            throw new UnsupportedModelException();
        }

        $oldModel = $this->mapper->cloneAttachment($model);
        $model->owner()->associate($data->ownerId);

        if ($model->isClean()) {
            return;
        }

        $this->conResolver->connection()->transaction(static function () use ($model): void {
            $model->save();
        });

        $this->eventDispatcher->dispatch(new AttachmentOwnershipChanged(
            $model,
            $oldModel,
            $this->causer,
        ));
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn (): ?Model => $this->causer = $causer);
    }
}
