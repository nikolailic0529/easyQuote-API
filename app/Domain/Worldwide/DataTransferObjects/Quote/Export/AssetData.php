<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class AssetData extends DataTransferObject
{
    public string $buy_currency_code;

    public string $vendor_short_code;

    public string $product_no;

    public string $service_sku;

    public string $description;

    public string $serial_no;

    public bool $is_serial_number_generated;

    public string $date_from;

    public string $date_to;

    public string $contract_duration = '';

    public int $qty;

    public string $price;

    public float $price_float;

    public string $pricing_document;

    public string $system_handle;

    public string $searchable;

    public string $service_level_description;

    public string $country_code;

    public string $machine_address_string;

    public string $state;

    public string $end_user_invoice_country_code = '';

    public string $end_user_invoice_state = '';
}
