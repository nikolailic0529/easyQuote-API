<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributionImportData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $distribution_id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $vendors;

    /**
     * @Constraints\Uuid
     */
    public string $country_id;

    /**
     * @Constraints\Uuid
     */
    public string $distribution_currency_id;

    /**
     * @Constraints\PositiveOrZero
     */
    public float $buy_price;

    public bool $calculate_list_price;

    public Carbon $distribution_expiry_date;
}
