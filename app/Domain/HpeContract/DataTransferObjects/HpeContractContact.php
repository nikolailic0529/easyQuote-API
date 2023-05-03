<?php

namespace App\Domain\HpeContract\DataTransferObjects;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

class HpeContractContact extends FlexibleDataTransferObject
{
    public ?string $org_name = null;
    public ?string $attn = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $post_code = null;
    public ?string $country = null;
    public ?string $city = null;

    public static function fromArray(array $array)
    {
        return new static($array);
    }
}
