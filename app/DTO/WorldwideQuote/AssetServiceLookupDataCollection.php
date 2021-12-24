<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class AssetServiceLookupDataCollection extends DataTransferObjectCollection
{
    public function current(): AssetServiceLookupData
    {
        return parent::current();
    }
}
