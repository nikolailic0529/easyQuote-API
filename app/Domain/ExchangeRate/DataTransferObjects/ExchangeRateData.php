<?php

namespace App\Domain\ExchangeRate\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class ExchangeRateData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $country_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $currency_id;

    public string $country_code;

    /**
     * @Constraints\Currency
     */
    public string $currency_code;

    /**
     * @Constraints\Date
     */
    public string $date;

    /**
     * @Constraints\PositiveOrZero
     */
    public float $exchange_rate;
}
