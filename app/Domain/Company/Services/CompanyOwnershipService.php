<?php

namespace App\Domain\Company\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Company\Events\CompanyOwnershipChanged;
use App\Domain\Company\Models\Company;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\Contracts\ProvidesLinkedModels;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use App\Domain\Worldwide\Queries\OpportunityQueries;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class CompanyOwnershipService implements ChangeOwnershipStrategy, ProvidesLinkedModels, CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly CompanyDataMapper $mapper,
        protected readonly OpportunityQueries $oppQueries,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof Company) {
            throw new UnsupportedModelException();
        }

        $oldModel = $this->mapper->cloneCompany($model);

        $originalOwner = $model->owner;
        $model->owner()->associate($data->ownerId);
        $model->salesUnit()->associate($data->salesUnitId);

        $this->conResolver->connection()
            ->transaction(function () use ($originalOwner, $data, $model): void {
                $model->save();

                if ($data->keepOriginalOwnerEditor && $originalOwner && $originalOwner->isNot($model->user)) {
                    $model->sharingUsers()->sync($originalOwner);
                } else {
                    $model->sharingUsers()->detach();
                }
            });

        $this->eventDispatcher->dispatch(new CompanyOwnershipChanged(
            $model,
            $oldModel,
            $this->causer,
        ));
    }

    public function getLinkedModels(Model $model): iterable
    {
        if (!$model instanceof Company) {
            return [];
        }

        yield from $model->contacts;
        yield from $model->addresses;
        yield from $model->notes;
        yield from $model->tasks;
        yield from $model->ownAppointments;
        yield from $this->oppQueries->baseOpenOpportunitiesOfCompanyQuery($model)->lazyById();
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }
}
