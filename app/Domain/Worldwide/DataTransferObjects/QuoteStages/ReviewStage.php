<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\SelectedDistributionRowsCollection;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class ReviewStage extends DataTransferObject
{
    public SelectedDistributionRowsCollection $selected_distribution_rows;

    public int $stage = ContractQuoteStage::REVIEW;
}
