<?php

namespace App\Services\VendorServices\Models;

use Spatie\DataTransferObject\DataTransferObject;

final class CheckSalesOrderResultBcAddress extends DataTransferObject
{
    protected bool $ignoreMissing = true;

    public string $id;

    public string $customer_id;

    public string $order_id;

    public string $address_type;

    public ?string $address_1;

    public ?string $address_2;

    public ?string $city;

    public ?string $state;

    public ?string $state_code;

    public ?string $post_code;

    public ?string $country_code;

    public ?float $lat;

    public ?float $lng;

    public int $is_default;

    public string $created_at;

    public string $updated_at;

    public ?string $deleted_at;
}