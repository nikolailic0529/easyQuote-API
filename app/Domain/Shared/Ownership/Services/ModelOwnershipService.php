<?php

namespace App\Domain\Shared\Ownership\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Shared\Ownership\ChangeOwnershipStrategyCollection;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\Contracts\ProvidesLinkedModels;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use Illuminate\Database\Eloquent\Model;

final class ModelOwnershipService implements CauserAware
{
    private ?Model $causer = null;

    public function __construct(
        protected readonly ChangeOwnershipStrategyCollection $strategies,
    ) {
    }

    /**
     * @throws UnsupportedModelException
     */
    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        $strategy = $this->mustChangeOwnershipNonRecursive($model, $data);

        if ($data->transferLinkedRecords && $strategy instanceof ProvidesLinkedModels) {
            foreach ($strategy->getLinkedModels($model) as $linkedModel) {
                $this->mustChangeOwnershipNonRecursive($linkedModel, $data);
            }
        }
    }

    /**
     * @throws UnsupportedModelException
     */
    private function mustChangeOwnershipNonRecursive(Model $model, ChangeOwnershipData $data): ChangeOwnershipStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy instanceof CauserAware) {
                $strategy->setCauser($this->causer);
            }

            try {
                $strategy->changeOwnership($model, $data);

                return $strategy;
            } catch (UnsupportedModelException) {
            }
        }

        throw new UnsupportedModelException($model::class);
    }

    public function setCauser(?Model $causer): static
    {
        $this->causer = $causer;

        foreach ($this->strategies as $strategy) {
            if ($strategy instanceof CauserAware) {
                $strategy->setCauser($causer);
            }
        }

        return $this;
    }
}
