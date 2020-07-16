<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\FlexibleDataTransferObject;

class HpeContractContact extends FlexibleDataTransferObject
{
    public ?string $org_name = null, $attn = null, $email = null, $phone = null, $address = null, $post_code = null, $country = null, $city = null;

    public static function fromArray(array $array)
    {
        return new static($array);
    }
}