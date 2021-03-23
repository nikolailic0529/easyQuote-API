<?php

namespace App\DTO;

use App\Models\QuoteFile\DistributionRowsGroup;
use Spatie\DataTransferObject\DataTransferObject;

class DistributionRowsLookupData extends DataTransferObject
{
    /** @var string[] */
    public array $input;

    public ?DistributionRowsGroup $rows_group = null;
}