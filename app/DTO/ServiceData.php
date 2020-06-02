<?php

namespace App\DTO;

use Spatie\DataTransferObject\FlexibleDataTransferObject;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class ServiceData extends FlexibleDataTransferObject
{
    public string $serial, $model, $type, $sku;
    
    public ?string $description, $product_image;

    public Carbon $warranty_start_date;

    public Carbon $warranty_end_date;

    public int $warranty_status;

    public WarrantyCollection $warranties;

    public static function create(array $data): ServiceData
    {
        $serial = Arr::get($data, 'serial');
        $model = Arr::get($data, 'model');
        $type = Arr::get($data, 'type');
        $sku = Arr::get($data, 'sku');
        $description = Arr::get($data, 'description');
        $product_image = Arr::get($data, 'product_image');
        $warranty_status = Arr::get($data, 'warranty_status');

        $warranty_start_date = Carbon::parse(Arr::get($data, 'warranty_start_date'));
        $warranty_end_date = Carbon::parse(Arr::get($data, 'warranty_end_date'));

        $warranties = WarrantyCollection::create(Arr::get($data, 'Warranties', []));

        return new static([
            'serial' => $serial,
            'model' => $model,
            'type' => $type,
            'sku' => $sku,
            'description' => $description,
            'product_image' => $product_image,
            'warranty_status' => $warranty_status,
            'warranty_start_date' => $warranty_start_date,
            'warranty_end_date' => $warranty_end_date,
            'warranties' => $warranties,
        ]);
    }
}
