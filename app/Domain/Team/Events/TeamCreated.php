<?php

namespace App\Domain\Team\Events;

use App\Domain\Team\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TeamCreated
{
    use Dispatchable;
    use SerializesModels;

    private Team $team;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }
}
