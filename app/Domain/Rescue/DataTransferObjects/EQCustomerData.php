<?php

namespace App\Domain\Rescue\DataTransferObjects;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class EQCustomerData extends DataTransferObject
{
    public string $int_company_id;

    public string $customer_name;

    public string $rfq_number;

    public int $sequence_number;

    public ?array $service_levels;

    public Carbon $quotation_valid_until;

    public Carbon $support_start_date;

    public Carbon $support_end_date;

    public string $invoicing_terms;

    /** @var string[] */
    public array $address_keys;

    /** @var string[] */
    public array $contact_keys;

    public ?string $email;

    public ?string $vat;

    public ?string $phone;

    /** @var string[] */
    public array $vendor_keys = [];
}
