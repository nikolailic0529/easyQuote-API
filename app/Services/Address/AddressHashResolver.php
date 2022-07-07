<?php

namespace App\Services\Address;

use App\Models\Address;
use App\Models\ImportedAddress;

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