<?php

namespace App\DTO\QuoteStages;

use App\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PackAssetsReviewStage extends DataTransferObject
{
    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $selected_rows;

    public bool $reject = false;

    /**
     * @Constraints\Choice({"sku", "serial_no", "product_name", "expiry_date", "price", "service_level_description", "vendor_short_code", "machine_address"})
     *
     * @var string|null
     */
    public ?string $sort_rows_column;

    /**
     * @Constraints\Choice({"asc","desc"})
     */
    public string $sort_rows_direction = 'asc';

    public int $stage = PackQuoteStage::ASSETS_REVIEW;
}
