<?php

namespace App\DTO\QuoteStages;

use App\Enum\PackQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PackDetailsStage extends DataTransferObject
{
    public string $pricing_document;

    public string $service_agreement_id;

    public string $system_handle;

    public ?string $additional_details;

    public int $stage = PackQuoteStage::DETAIL;
}
