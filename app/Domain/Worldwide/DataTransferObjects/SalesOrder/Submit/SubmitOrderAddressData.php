<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitOrderAddressData extends DataTransferObject
{
    /**
     * @Constraints\Choice({"Machine","Hardware","Invoice"})
     */
    public string $address_type;

    public string $address_1;

    public ?string $address_2;

    public ?string $state;

    public ?string $state_code;

    /**
     * @Constraints\Country(message="Address does not have valid Country Code.")
     */
    public string $country_code;

    /**
     * @Constraints\NotBlank(message="Address does not have City.")
     */
    public ?string $city;

    /**
     * @Constraints\NotBlank(message="Address does not have Post Code.")
     */
    public ?string $post_code;
}
