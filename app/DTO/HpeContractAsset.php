<?php

namespace App\DTO;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\FlexibleDataTransferObject;

class HpeContractAsset extends FlexibleDataTransferObject
{
    public int $id;

    public string $no = "000000";

    public int $product_quantity;

    public string $product_number, $product_description, $support_start_date, $support_end_date;

    public ?string  $support_account_reference, $contract_number, $serial_number;

    public bool $is_selected = false;
}
