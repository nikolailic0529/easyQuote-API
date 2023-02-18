<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use Spatie\DataTransferObject\DataTransferObject;

final class AssetsLookupData extends DataTransferObject
{
    /** @var string[] */
    public array $input;

    public ?WorldwideQuoteAssetsGroup $assets_group = null;
}
