<?php

namespace App\Domain\Worldwide\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ChangesAddressOwnership
{
    public function changeAddressOwnership(Model $address, string $ownerId);
}