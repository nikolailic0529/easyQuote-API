<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class PackAssetsCreationStage extends DataTransferObject
{
    public int $stage = PackQuoteStage::ASSETS_CREATE;
}
