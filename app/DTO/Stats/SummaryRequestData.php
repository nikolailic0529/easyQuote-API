<?php

namespace App\DTO\Stats;

use Carbon\CarbonPeriod;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SummaryRequestData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $user_id;

    public ?CarbonPeriod $period;

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

    public bool $own_entities_only = false;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $entity_types;
}
