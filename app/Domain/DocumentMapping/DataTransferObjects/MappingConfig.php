<?php

namespace App\Domain\DocumentMapping\DataTransferObjects;

use App\Domain\Date\Enum\DateFormatEnum;
use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class MappingConfig extends DataTransferObject
{
    public ?Carbon $default_date_from = null;

    public ?Carbon $default_date_to = null;

    public ?CarbonInterval $contract_duration = null;

    public bool $is_contract_duration_checked = false;

    public int $default_qty = 1;

    public bool $calculate_list_price = false;

    public float $exchange_rate_value = 1;

    public DateFormatEnum $file_date_format = DateFormatEnum::Auto;
}
