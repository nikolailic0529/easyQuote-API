<?php

namespace App\Events\Address;

use App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class AddressDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly Address $address,
        public readonly ?Model $causer = null
    ) {
    }
}
