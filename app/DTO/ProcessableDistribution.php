<?php

namespace App\DTO;

use App\Enum\DateFormatEnum;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class ProcessableDistribution extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $vendors;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $address_ids;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $contact_ids;

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
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $buy_currency_id;

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

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $distributor_file_id;

    public ?int $distributor_file_page = null;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $schedule_file_id = null;

    public ?int $schedule_file_page = null;

    public DateFormatEnum $file_date_format;
}
