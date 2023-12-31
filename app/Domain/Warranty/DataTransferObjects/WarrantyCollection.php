<?php

namespace App\Domain\Warranty\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class WarrantyCollection extends DataTransferObjectCollection
{
    public static function create(array $data): WarrantyCollection
    {
        return new static(
            array_map(fn ($parameters) => WarrantyData::create($parameters), $data)
        );
    }

    public function current(): WarrantyData
    {
        return parent::current();
    }
}
