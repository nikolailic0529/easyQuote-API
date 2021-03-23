<?php

namespace App\DTO\Team;

use Spatie\DataTransferObject\DataTransferObject;

final class CreateTeamData extends DataTransferObject
{
    public string $team_name;

    public ?float $monthly_goal_amount;
}
