<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributionAddressData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $address_id = null;

    /**
     * @Constraints\NotBlank(allowNull=true)
     *
     * @var string|null
     */
    public ?string $address_1;

    /**
     * @Constraints\Choice({"Invoice", "Machine", "Software", "Client"})
     *
     * @var string
     */
    public string $address_type;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $country_id;

    public ?string $address_2;

    public ?string $city;

    public ?string $state;

    public ?string $post_code;

    public bool $is_default;
}
