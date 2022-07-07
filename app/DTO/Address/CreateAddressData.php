<?php

namespace App\DTO\Address;

use Spatie\DataTransferObject\DataTransferObject;

final class CreateAddressData extends DataTransferObject
{
    public string $address_type;
    public ?string $address_1;
    public ?string $address_2;
    public ?string $city;
    public ?string $state;
    public ?string $state_code;
    public ?string $post_code;
    public ?string $country_id;
    public ?string $contact_id;
}