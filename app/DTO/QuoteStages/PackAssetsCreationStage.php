<?php

namespace App\DTO\QuoteStages;

use App\Enum\ContractQuoteStage;
use App\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PackAssetsCreationStage extends DataTransferObject
{
    public int $stage = PackQuoteStage::ASSETS_CREATE;
}
