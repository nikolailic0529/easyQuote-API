<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\Enum\ContractQuoteStage;
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

    public string $payment_terms;

    public bool $are_end_user_addresses_available;

    public bool $are_end_user_contacts_available;

    public int $stage = ContractQuoteStage::IMPORT;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\DistributionImportStageData[]
     */
    public array $distributions_data;
}
