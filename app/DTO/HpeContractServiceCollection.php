<?php

namespace App\DTO;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\DataTransferObjectCollection;
use Spatie\DataTransferObject\FlexibleDataTransferObject;

class HpeContractServiceCollection extends DataTransferObjectCollection
{
    public static function fromCollection(Collection $collection)
    {
        $data = $collection->map(fn (HpeContractService $asset, $key) =>
            (new HpeContractService(array_merge($asset->toArray(), ['no' => sprintf("%'.06d", ++$key)])))->except('contract_number')
        );

        return new static($data->toArray());
    }
}