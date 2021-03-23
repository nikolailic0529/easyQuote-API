<?php

namespace App\DTO;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class ProcessableDistribution extends DataTransferObject
{
    public string $id;

    /** @var string[] */
    public array $vendors;

    public string $country_id;

    public string $distribution_currency_id;

    public string $distributor_file_id;

    public Carbon $distribution_expiry_date;

    public ?int $distributor_file_page = null;

    public ?string $schedule_file_id = null;

    public ?int $schedule_file_page = null;

    public float $buy_price;

    public ?bool $calculate_list_price = null;
}
