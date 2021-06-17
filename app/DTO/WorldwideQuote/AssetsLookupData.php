<?php

namespace App\DTO\WorldwideQuote;

use App\Models\WorldwideQuoteAssetsGroup;
use Spatie\DataTransferObject\DataTransferObject;

final class AssetsLookupData extends DataTransferObject
{
    /** @var string[] */
    public array $input;

    public ?WorldwideQuoteAssetsGroup $assets_group = null;
}