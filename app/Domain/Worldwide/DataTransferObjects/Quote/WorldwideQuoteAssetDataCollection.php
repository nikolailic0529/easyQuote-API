<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class WorldwideQuoteAssetDataCollection extends DataTransferObjectCollection
{
    public function current(): WorldwideQuoteAssetData
    {
        return parent::current();
    }
}
