<?php

namespace App\DTO\Asset;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateAssetData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $asset_category_id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $address_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $vendor_id;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $vendor_short_code;

    public float $unit_price;

    /**
     * @Constraints\Date
     *
     * @var string
     */
    public string $base_warranty_start_date;

    /**
     * @Constraints\Date
     *
     * @var string
     */
    public string $base_warranty_end_date;

    /**
     * @Constraints\Date
     *
     * @var string
     */
    public string $active_warranty_start_date;

    /**
     * @Constraints\Date
     *
     * @var string
     */
    public string $active_warranty_end_date;

    public ?string $item_number;

    public string $product_number;

    public string $serial_number;

    public ?string $product_description;

    public ?string $product_image;
}
