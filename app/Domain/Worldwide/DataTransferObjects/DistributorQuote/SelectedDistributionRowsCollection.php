<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class SelectedDistributionRowsCollection extends DataTransferObjectCollection
{
    public function current(): SelectedDistributionRows
    {
        return parent::current();
    }
}
