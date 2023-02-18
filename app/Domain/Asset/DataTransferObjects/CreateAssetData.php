<?php

namespace App\Domain\Asset\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateAssetData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $asset_category_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $address_id;

    /**
     * @Constraints\Uuid
     */
    public string $vendor_id;

    /**
     * @Constraints\NotBlank
     */
    public string $vendor_short_code;

    public float $unit_price;

    /**
     * @Constraints\Date
     */
    public string $base_warranty_start_date;

    /**
     * @Constraints\Date
     */
    public string $base_warranty_end_date;

    /**
     * @Constraints\Date
     */
    public string $active_warranty_start_date;

    /**
     * @Constraints\Date
     */
    public string $active_warranty_end_date;

    public ?string $item_number;

    public string $product_number;

    public string $serial_number;

    public ?string $product_description;

    public ?string $product_image;
}
