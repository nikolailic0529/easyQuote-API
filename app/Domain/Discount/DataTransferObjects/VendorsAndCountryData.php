<?php

namespace App\Domain\Discount\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class VendorsAndCountryData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank()
     * @Constraints\All(@Constraints\Uuid())
     *
     * @var string[]
     */
    public array $vendor_keys;

    /**
     * @Constraints\NotBlank()
     * @Constraints\Uuid()
     */
    public string $country_id;
}
