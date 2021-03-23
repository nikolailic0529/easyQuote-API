<?php

namespace App\DTO;

use Illuminate\Support\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class MappedRowSettings extends DataTransferObject
{
    public ?Carbon $default_date_from = null;

    public ?Carbon $default_date_to = null;

    public int $default_qty = 1;

    public bool $calculate_list_price = false;

    public float $exchange_rate_value = 1;
}
