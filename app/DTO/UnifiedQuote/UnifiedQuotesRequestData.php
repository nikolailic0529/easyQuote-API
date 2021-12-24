<?php

namespace App\DTO\UnifiedQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UnifiedQuotesRequestData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $acting_user_id = null;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $acting_user_team_id = null;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $acting_user_led_teams = [];

    public bool $get_rescue_entities = true;

    public bool $get_worldwide_entities = true;

    public bool $get_any_owner_entities = true;

    /**
     * @Constraints\IsTrue(message="At least one entity type must be defined.")
     *
     * @return bool
     */
    public function isAnyEntityTypeDefined(): bool
    {
        return $this->get_worldwide_entities || $this->get_rescue_entities;
    }
}
