<?php

namespace App\Domain\Worldwide\Services\Opportunity;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Note\Models\Note;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\Contracts\ProvidesLinkedModels;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use App\Domain\Worldwide\Events\Opportunity\OpportunityOwnershipChanged;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OpportunityOwnershipService implements ChangeOwnershipStrategy, ProvidesLinkedModels, CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly OpportunityDataMapper $mapper,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Opportunity) {
            throw new UnsupportedModelException();
        }

        $oldModel = $this->mapper->cloneOpportunity($model);

        $originalOwner = $model->owner;
        $model->owner()->associate($data->ownerId);
        $model->salesUnit()->associate($data->salesUnitId);

        $this->conResolver->connection()->transaction(static function () use ($originalOwner, $data, $model): void {
            $model->save();

            if ($data->keepOriginalOwnerEditor && $originalOwner && $originalOwner->isNot($model->owner)) {
                $model->sharingUsers()->sync($originalOwner);
            } else {
                $model->sharingUsers()->detach();
            }
        });

        $this->eventDispatcher->dispatch(new OpportunityOwnershipChanged(
            $model,
            $oldModel,
            $this->causer,
        ));
    }

    public function getLinkedModels(Model $model): iterable
    {
        if (!$model instanceof Opportunity) {
            return [];
        }

        yield from $model->getRelationValue('notes')->reject(static function (Note $note): bool {
            return $note->getFlag(Note::SYSTEM);
        });
        yield from $model->tasks;
        yield from $model->ownAppointments;
        yield from $model->attachments;

        yield from Collection::make([
            $model->primaryAccount,
            $model->endUser,
        ])
            ->filter()
            ->unique()
            ->values();
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }
}
