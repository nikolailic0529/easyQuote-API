<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\Enum\ContractQuoteStage;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

final class DraftStage extends DataTransferObject
{
    public Carbon $quote_closing_date;

    public ?string $additional_notes;

    public int $stage = ContractQuoteStage::COMPLETED;
}
