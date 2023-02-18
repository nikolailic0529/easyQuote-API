<?php

namespace App\Domain\Address\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Address\Models\ImportedAddress;

class AddressHashResolver
{
    public function __invoke(Address|ImportedAddress $address): string
    {
        return md5(implode('~', [
            $address->address_type,
            $address->address_1,
            $address->address_2,
            $address->city,
            $address->post_code,
            $address->state,
            $address->country_id,
        ]));
    }
}
