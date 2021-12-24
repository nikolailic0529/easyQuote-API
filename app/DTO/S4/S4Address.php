<?php

namespace App\DTO\S4;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

class S4Address extends FlexibleDataTransferObject
{
    public string $address_type;

    public string $address_1;

    public ?string $address_2 = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $post_code = null;

    public ?string $state_code = null;

    public ?string $country_code = null;

    public ?string $contact_name = null;

    public ?string $contact_number = null;

    public ?string $contact_phone = null;

    public ?string $contact_email = null;
}