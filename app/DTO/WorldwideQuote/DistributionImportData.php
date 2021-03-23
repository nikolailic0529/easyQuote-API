<?php

namespace App\DTO\WorldwideQuote;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributionImportData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $distribution_id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $vendors;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $country_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $distribution_currency_id;

    /**
     * @Constraints\PositiveOrZero
     *
     * @var float
     */
    public float $buy_price;

    /**
     * @var bool
     */
    public bool $calculate_list_price;

    public Carbon $distribution_expiry_date;
}
