<?php

namespace App\Services\Address;

use App\Models\Address;
use App\Models\Contact;
use App\Models\ImportedAddress;
use Webpatser\Uuid\Uuid;

class ImportedAddressToAddressProjector
{
    public function __invoke(ImportedAddress $importedAddress): Address
    {
        return tap(new Address(), function (Address $address) use ($importedAddress): void {
            $address->{$address->getKeyName()} = (string)Uuid::generate(4);
            $address->pl_reference = $importedAddress->pl_reference;
            $address->user()->associate($importedAddress->owner);
            $address->address_type = $importedAddress->address_type;
            $address->address_1 = $importedAddress->address_1;
            $address->address_2 = $importedAddress->address_2;
            $address->city = $importedAddress->city;
            $address->post_code = $importedAddress->post_code;
            $address->state = $importedAddress->state;
            $address->state_code = $importedAddress->state_code;
            $address->country()->associate($importedAddress->country_id);

            $address->updateTimestamps();
        });
    }
}