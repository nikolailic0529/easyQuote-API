<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Date\Enum\DateFormatEnum;
use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributionImportStageData extends DataTransferObject
{
    #[Constraints\Uuid]
    public string $distribution_id;

    #[Constraints\All(constraints: new Constraints\Uuid())]
    public array $vendors;

    #[Constraints\All(constraints: new Constraints\Uuid())]
    public array $address_ids;

    #[Constraints\All(constraints: new Constraints\Uuid())]
    public array $contact_ids;

    #[Constraints\Uuid]
    public string $country_id;

    #[Constraints\Uuid]
    public string $distribution_currency_id;

    public ?float $distribution_currency_quote_currency_exchange_rate_value;

    public ?float $distribution_currency_quote_currency_exchange_rate_margin;

    #[Constraints\Uuid]
    public string $buy_currency_id;

    #[Constraints\PositiveOrZero]
    public float $buy_price;

    public bool $calculate_list_price;

    public Carbon $distribution_expiry_date;

    public DateFormatEnum $file_date_format;
}
