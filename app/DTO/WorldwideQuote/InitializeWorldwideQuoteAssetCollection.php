<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class InitializeWorldwideQuoteAssetCollection extends DataTransferObjectCollection
{
    public function current(): InitializeWorldwideQuoteAssetData
    {
        return parent::current();
    }
}