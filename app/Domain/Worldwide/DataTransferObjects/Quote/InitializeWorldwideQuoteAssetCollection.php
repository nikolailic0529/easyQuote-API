<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class InitializeWorldwideQuoteAssetCollection extends DataTransferObjectCollection
{
    public function current(): InitializeWorldwideQuoteAssetData
    {
        return parent::current();
    }
}
