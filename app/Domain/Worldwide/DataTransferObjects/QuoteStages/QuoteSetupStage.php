<?php

namespace App\Domain\Worldwide\DataTransferObjects\QuoteStages;

use App\Domain\Worldwide\Enum\PackQuoteStage;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class QuoteSetupStage extends DataTransferObject
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

    public Carbon $quote_expiry_date;

    public string $payment_terms;

    public bool $are_end_user_addresses_available;

    public bool $are_end_user_contacts_available;

    /**
     * @Constraints\PositiveOrZero
     */
    public float $buy_price;

    public int $stage = PackQuoteStage::CONTACTS;
}
