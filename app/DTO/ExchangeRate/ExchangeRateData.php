<?php

namespace App\DTO\ExchangeRate;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class ExchangeRateData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $country_id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $currency_id;

    /**
     * @var string
     */
    public string $country_code;

    /**
     * @Constraints\Currency
     *
     * @var string
     */
    public string $currency_code;

    /**
     * @Constraints\Date
     *
     * @var string
     */
    public string $date;

    /**
     * @Constraints\PositiveOrZero
     *
     * @var float
     */
    public float $exchange_rate;
}
