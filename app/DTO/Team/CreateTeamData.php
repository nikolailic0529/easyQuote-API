<?php

namespace App\DTO\Team;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateTeamData extends DataTransferObject
{
    public string $team_name;

    /**
     * @Constraints\Uuid
     * @Constraints\Choice(callback="getBusinessDivisions")
     *
     * @var string
     */
    public string $business_division_id;

    public ?float $monthly_goal_amount;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $team_leader_user_ids;

    public function getBusinessDivisions(): array
    {
        return [BD_RESCUE, BD_WORLDWIDE];
    }
}
