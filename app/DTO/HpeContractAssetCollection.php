<?php

namespace App\DTO;

use App\Models\HpeContractData;
use Illuminate\Database\Eloquent\Collection;
use Spatie\DataTransferObject\DataTransferObjectCollection;

class HpeContractAssetCollection extends DataTransferObjectCollection
{
    public static function fromCollection(Collection $collection)
    {
        $data = $collection->map(fn (HpeContractData $asset, $key) =>
            new HpeContractAsset(array_merge($asset->toArray(), ['no' => sprintf("%'.06d", ++$key)]))
        );

        return new static($data->toArray());
    }
}