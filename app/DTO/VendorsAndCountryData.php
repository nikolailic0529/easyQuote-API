<?php

namespace App\DTO;

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
     * 
     * @var string
     */
    public string $country_id;
}