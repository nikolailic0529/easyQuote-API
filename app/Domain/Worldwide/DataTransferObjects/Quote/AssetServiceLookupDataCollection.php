<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class AssetServiceLookupDataCollection extends DataTransferObjectCollection
{
    public function current(): AssetServiceLookupData
    {
        return parent::current();
    }
}
