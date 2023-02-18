<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class PackDetailsStage extends DataTransferObject
{
    public string $pricing_document;

    public string $service_agreement_id;

    public string $system_handle;

    public ?string $additional_details;

    public int $stage = PackQuoteStage::DETAIL;
}
