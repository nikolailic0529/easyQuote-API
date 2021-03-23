<?php

namespace App\DTO\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitOrderAddressData extends DataTransferObject
{
    /**
     * @Constraints\Choice({"Machine","Invoice"})
     *
     * @var string
     */
    public string $address_type;

    public string $address_1;

    public ?string $address_2;

    public ?string $state;

    public ?string $state_code;

    /**
     * @Constraints\Country(message="Address does not have valid Country Code.")
     *
     * @var string
     */
    public string $country_code;

    /**
     * @Constraints\NotBlank(message="Address does not have City.")
     *
     * @var string|null
     */
    public ?string $city;

    /**
     * @Constraints\NotBlank(message="Address does not have Post Code.")
     *
     * @var string|null
     */
    public ?string $post_code;
}
