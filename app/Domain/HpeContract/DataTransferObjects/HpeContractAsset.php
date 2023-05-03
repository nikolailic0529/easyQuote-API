<?php

namespace App\Domain\HpeContract\DataTransferObjects;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

class HpeContractAsset extends FlexibleDataTransferObject
{
    public int $id;

    public string $no = '000000';

    public int $product_quantity;

    public string $product_number;
    public string $product_description;
    public string $support_start_date;
    public string $support_end_date;

    public ?string  $support_account_reference;
    public ?string  $contract_number;
    public ?string  $serial_number;

    public bool $is_selected = false;
}
