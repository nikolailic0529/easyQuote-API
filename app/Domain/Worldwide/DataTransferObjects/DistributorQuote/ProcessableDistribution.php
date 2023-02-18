<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use App\Domain\Date\Enum\DateFormatEnum;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class ProcessableDistribution extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $vendors;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $address_ids;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $contact_ids;

    /**
     * @Constraints\Uuid
     */
    public string $country_id;

    /**
     * @Constraints\Uuid
     */
    public string $distribution_currency_id;

    /**
     * @Constraints\Uuid
     */
    public string $buy_currency_id;

    /**
     * @Constraints\PositiveOrZero
     */
    public float $buy_price;

    public bool $calculate_list_price;

    public Carbon $distribution_expiry_date;

    /**
     * @Constraints\Uuid
     */
    public string $distributor_file_id;

    public ?int $distributor_file_page = null;

    /**
     * @Constraints\Uuid
     */
    public ?string $schedule_file_id = null;

    public ?int $schedule_file_page = null;

    public DateFormatEnum $file_date_format;
}
