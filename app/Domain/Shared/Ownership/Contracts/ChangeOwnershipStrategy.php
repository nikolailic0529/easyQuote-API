<?php

namespace App\Domain\Shared\Ownership\Contracts;

use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use Illuminate\Database\Eloquent\Model;

interface ChangeOwnershipStrategy
{
    /**
     * @throws UnsupportedModelException
     */
    public function changeOwnership(Model $model, ChangeOwnershipData $data): void;
}
