<?php

namespace App\DTO\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitOrderLineData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $line_id;

    public float $unit_price;

    /**
     * @Constraints\NotBlank(message="SKU is required.")
     *
     * @var string
     */
    public string $sku;

    public float $buy_price;

    public ?string $service_description;

    public ?string $product_description;

    /**
     * @Constraints\NotBlank(message="Serial number is required.")
     *
     * @var string
     */
    public string $serial_number;

    public int $quantity;

    /**
     * @Constraints\NotBlank(message="Service SKU is required.")
     *
     * @var string
     */
    public string $service_sku;

    /**
     * @Constraints\NotBlank(message="Vendor is required.")
     *
     * @var string
     */
    public string $vendor_short_code;

    public ?string $distributor_name;

    public bool $discount_applied;

    /**
     * @Constraints\Country(message="Invalid Machine Country Code.")
     *
     * @var string
     */
    public string $machine_country_code;

    public string $currency_code;
}
