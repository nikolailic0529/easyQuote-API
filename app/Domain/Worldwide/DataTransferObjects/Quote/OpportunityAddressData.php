<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class OpportunityAddressData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $address_id = null;

    /**
     * @Constraints\NotBlank(allowNull=true)
     */
    public ?string $address_1;

    /**
     * @Constraints\Choice({"Invoice", "Machine", "Software", "Client"})
     */
    public string $address_type;

    /**
     * @Constraints\Uuid
     */
    public string $country_id;

    public ?string $address_2;

    public ?string $city;

    public ?string $state;

    public ?string $post_code;

    public bool $is_default;
}
