<?php

namespace App\Events\Address;

use App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class AddressUpdated
{
    use Dispatchable;

    /**
     * @param  list<string>  $companyRelations
     */
    public function __construct(
        public readonly Address $address,
        public readonly array $companyRelations,
        public readonly Address $newAddress,
        public readonly ?Model $causer = null
    ) {
    }
}
