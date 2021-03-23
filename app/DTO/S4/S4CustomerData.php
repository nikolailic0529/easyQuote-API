<?php

namespace App\DTO\S4;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class S4CustomerData extends DataTransferObject
{
    public string $customer_name;

    public string $rfq_number;

    /** @var string[]|null */
    public ?array $service_levels;

    public Carbon $quotation_valid_until;

    public Carbon $support_start_date;

    public Carbon $support_end_date;

    public string $invoicing_terms;

    public string $country_code;
    
    public S4AddressCollection $addresses;
}