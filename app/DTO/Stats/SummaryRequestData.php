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
    public ?string $acting_user_id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $acting_user_team_id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $acting_user_led_teams;

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

    public bool $any_owner_entities;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $entity_types;
}
