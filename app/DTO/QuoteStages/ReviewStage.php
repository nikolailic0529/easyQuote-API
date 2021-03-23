<?php

namespace App\DTO\QuoteStages;

use App\DTO\SelectedDistributionRowsCollection;
use App\Enum\ContractQuoteStage;
use Spatie\DataTransferObject\DataTransferObject;

final class ReviewStage extends DataTransferObject
{
    public SelectedDistributionRowsCollection $selected_distribution_rows;

    public int $stage = ContractQuoteStage::REVIEW;
}
