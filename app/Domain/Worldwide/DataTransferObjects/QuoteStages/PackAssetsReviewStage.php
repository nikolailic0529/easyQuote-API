<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PackAssetsReviewStage extends DataTransferObject
{
    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $selected_rows;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $selected_groups;

    public bool $reject = false;

    /**
     * @Constraints\Choice({"sku", "serial_no", "product_name", "expiry_date", "price", "service_level_description", "vendor_short_code", "machine_address", "buy_currency_code"})
     */
    public ?string $sort_rows_column;

    /**
     * @Constraints\Choice({"asc","desc"})
     */
    public string $sort_rows_direction = 'asc';

    /**
     * @Constraints\Choice({"group_name", "search_text", "assets_count", "assets_sum"})
     */
    public ?string $sort_assets_groups_column;

    /**
     * @Constraints\Choice({"asc","desc"})
     */
    public string $sort_assets_groups_direction = 'asc';

    public bool $use_groups = false;

    public int $stage = PackQuoteStage::ASSETS_REVIEW;
}
