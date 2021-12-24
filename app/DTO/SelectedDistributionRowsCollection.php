<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class SelectedDistributionRowsCollection extends DataTransferObjectCollection
{
    public function current(): SelectedDistributionRows
    {
        return parent::current();
    }
}