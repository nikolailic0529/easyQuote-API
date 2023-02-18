<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use App\Domain\Worldwide\Models\DistributionRowsGroup;
use Spatie\DataTransferObject\DataTransferObject;

class DistributionRowsLookupData extends DataTransferObject
{
    /** @var string[] */
    public array $input;

    public ?DistributionRowsGroup $rows_group = null;
}
