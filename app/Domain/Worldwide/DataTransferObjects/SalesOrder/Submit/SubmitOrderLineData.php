<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitOrderLineData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $line_id;

    public float $unit_price;

    /**
     * @Constraints\NotBlank(message="SKU is missing on one or more order lines.")
     */
    public string $sku;

    public float $buy_price;

    public ?string $service_description;

    public ?string $product_description;

    /**
     * @Constraints\NotBlank(message="Serial number is missing on one or more order lines.")
     */
    public string $serial_number;

    public int $quantity;

    /**
     * @Constraints\NotBlank(message="Service SKU is missing on one or more order lines.")
     */
    public string $service_sku;

    /**
     * @Constraints\NotBlank(message="Vendor is missing on one or more order lines.")
     */
    public string $vendor_short_code;

    public ?string $distributor_name;

    public bool $discount_applied;

    /**
     * @Constraints\NotBlank(message="Country Code is missing on one or more order lines.")
     * @Constraints\Country(message="One or more order lines have invalid machine country code.")
     */
    public string $machine_country_code;

    public string $currency_code;
}
