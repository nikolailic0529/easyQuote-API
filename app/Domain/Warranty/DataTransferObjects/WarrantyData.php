<?php

namespace App\Domain\Warranty\DataTransferObjects;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Spatie\DataTransferObject\FlexibleDataTransferObject;

class WarrantyData extends FlexibleDataTransferObject
{
    public ?string $warranty_id;
    public ?string $warranty_name;
    public ?string $warranty_type;
    public ?string $warranty_description;
    public ?string $country_code;

    public Carbon $warranty_start;
    public Carbon $warranty_end;

    public static function create(array $data): WarrantyData
    {
        $warranty_start = Carbon::parse(Arr::get($data, 'warranty_start'));
        $warranty_end = Carbon::parse(Arr::get($data, 'warranty_end'));

        return new static(compact('warranty_start', 'warranty_end') + $data);
    }
}
