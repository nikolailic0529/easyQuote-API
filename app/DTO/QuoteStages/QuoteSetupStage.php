<?php

namespace App\DTO\QuoteStages;

use App\DTO\WorldwideQuote\OpportunityAddressDataCollection;
use App\DTO\WorldwideQuote\OpportunityContactDataCollection;
use App\Enum\PackQuoteStage;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class QuoteSetupStage extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $company_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $quote_currency_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $quote_template_id;

    public Carbon $quote_expiry_date;

    public string $payment_terms;

    public bool $are_end_user_addresses_available;

    public bool $are_end_user_contacts_available;

    /**
     * @Constraints\PositiveOrZero
     *
     * @var float
     */
    public float $buy_price;

    public int $stage = PackQuoteStage::CONTACTS;
}
