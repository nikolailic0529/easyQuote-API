<?php

namespace App\DTO;

use Spatie\DataTransferObject\FlexibleDataTransferObject;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class WarrantyData extends FlexibleDataTransferObject
{
    public ?string $warranty_id, $warranty_name, $warranty_type, $warranty_description, $country_code;

    public Carbon $warranty_start, $warranty_end;

    public static function create(array $data): WarrantyData
    {
        $warranty_start = Carbon::parse(Arr::get($data, 'warranty_start'));
        $warranty_end = Carbon::parse(Arr::get($data, 'warranty_end'));

        return new static(compact('warranty_start', 'warranty_end') + $data);
    }
}