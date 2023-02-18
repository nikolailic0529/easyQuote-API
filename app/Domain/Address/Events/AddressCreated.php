<?php

namespace App\Domain\Address\Events;

use App\Domain\Address\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class AddressCreated
{
    use Dispatchable;

    /**
     * @param list<string> $companyRelations
     */
    public function __construct(
        public readonly Address $address,
        public readonly array $companyRelations,
        public readonly ?Model $causer = null
    ) {
    }
}
