<?php

namespace App\Domain\Stats\DataTransferObjects;

use Carbon\CarbonPeriod;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SummaryRequestData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $acting_user_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $acting_user_team_id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $acting_user_led_teams;

    public ?CarbonPeriod $period;

    /**
     * @Constraints\Uuid
     */
    public ?string $country_id;

    /**
     * @Constraints\Uuid
     */
    public ?string $currency_id;

    public bool $any_owner_entities;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $entity_types;
}
