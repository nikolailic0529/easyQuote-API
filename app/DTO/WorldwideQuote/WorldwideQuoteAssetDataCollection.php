<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class WorldwideQuoteAssetDataCollection extends DataTransferObjectCollection
{
    public function current(): WorldwideQuoteAssetData
    {
        return parent::current();
    }
}
