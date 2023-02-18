<?php

namespace App\Domain\VendorServices\DataTransferObjects;

use App\Domain\Warranty\DataTransferObjects\WarrantyCollection;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Spatie\DataTransferObject\FlexibleDataTransferObject;

class WarrantyLookupResult extends FlexibleDataTransferObject
{
    public string $serial;
    public string $model;
    public string $type;
    public string $sku;

    public ?string $description;

    public ?string $product_image;

    public Carbon $warranty_start_date;

    public Carbon $warranty_end_date;

    public int $warranty_status;

    public WarrantyCollection $warranties;

    public static function fromArray(array $data): WarrantyLookupResult
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
