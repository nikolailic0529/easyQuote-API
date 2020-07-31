<?php

namespace App\DTO;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

class HpeContractService extends FlexibleDataTransferObject
{
    public string $no = "000000";

    public ?string $contract_number;

    public ?string $service_description;

    public ?string $service_description_2;

    public ?string $service_code;

    public ?string $service_code_2;

    public array $service_levels = [];
}