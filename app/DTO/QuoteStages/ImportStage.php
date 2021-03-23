<?php

namespace App\DTO\QuoteStages;

use App\Enum\ContractQuoteStage;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;


final class ImportStage extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $company_id;

    /**
     * @Constraints\Uuid
     */
    public string $quote_currency_id;

    /**
     * @Constraints\Uuid
     */
    public string $quote_template_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $output_currency_id = null;

    public ?float $exchange_rate_margin = null;

    public Carbon $quote_expiry_date;

    public int $stage = ContractQuoteStage::IMPORT;

    /**
     * @var \App\DTO\WorldwideQuote\DistributionImportStageData[]
     */
    public array $distributions_data;
}
