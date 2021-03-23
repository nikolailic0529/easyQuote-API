<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class DistributionMappingCollection extends DataTransferObjectCollection
{
    public function current(): DistributionMapping
    {
        return parent::current();
    }

    public static function fromArray(array $collection): DistributionMappingCollection
    {
        $collection = array_map(fn ($array) => new DistributionMapping($array), $collection);

        return new static($collection);
    }
}