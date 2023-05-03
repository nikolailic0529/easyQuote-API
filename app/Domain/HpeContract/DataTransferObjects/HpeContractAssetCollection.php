<?php

namespace App\Domain\HpeContract\DataTransferObjects;

use App\Domain\HpeContract\Models\HpeContractData;
use Illuminate\Database\Eloquent\Collection;
use Spatie\DataTransferObject\DataTransferObjectCollection;

class HpeContractAssetCollection extends DataTransferObjectCollection
{
    public static function fromCollection(Collection $collection)
    {
        $data = $collection->map(fn (HpeContractData $asset, $key) => new HpeContractAsset(array_merge($asset->toArray(), [
            'no' => sprintf("%'.06d", ++$key),
            'support_start_date' => optional($asset->support_start_date)->format(config('date.format_eu')),
            'support_end_date' => optional($asset->support_end_date)->format(config('date.format_eu')),
        ]))
        );

        return new static($data->toArray());
    }
}
