<?php

namespace App\DTO\Address;

use App\DTO\Enum\DataTransferValueOption;
use Spatie\DataTransferObject\DataTransferObject;

final class UpdateAddressData extends DataTransferObject
{
    public string $address_type;
    public ?string $address_1;
    public ?string $address_2;
    public ?string $city;
    public ?string $state;
    public ?string $state_code;
    public ?string $post_code;
    public ?string $country_id;

    /** @var string|\App\DTO\Enum\DataTransferValueOption */
    public string|DataTransferValueOption $contact_id = DataTransferValueOption::Miss;
}