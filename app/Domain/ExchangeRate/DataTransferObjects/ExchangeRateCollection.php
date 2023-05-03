<?php

namespace App\Domain\ExchangeRate\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class ExchangeRateCollection extends DataTransferObjectCollection
{
    public function current(): ExchangeRateData
    {
        return parent::current();
    }
}
