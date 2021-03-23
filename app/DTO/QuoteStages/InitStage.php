<?php

namespace App\DTO\QuoteStages;

use App\Enum\ContractQuoteStage;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class InitStage extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $opportunity_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $user_id;

    /**
     * @Constraints\Uuid
     * @Constraints\Choice({"c4da2cab-7fd0-4f60-87df-2cc9ea602fee", "c3c9d470-cb8b-48a2-9d3f-3614534b24a3"})
     *
     * @var string
     */
    public string $contract_type_id;

    public Carbon $quote_expiry_date;

    public int $stage = ContractQuoteStage::OPPORTUNITY;
}
