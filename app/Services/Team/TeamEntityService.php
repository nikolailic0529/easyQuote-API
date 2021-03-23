<?php

namespace App\Services\Team;

use App\DTO\{Team\CreateTeamData, Team\UpdateTeamData};
use App\Events\{Team\TeamCreated, Team\TeamDeleted, Team\TeamUpdated};
use App\Models\Team;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Events\Dispatcher as EventDispatcher;

class TeamEntityService
{
    protected ConnectionInterface $connection;

    protected EventDispatcher $eventDispatcher;

    /**
     * TeamEntityService constructor.
     * @param ConnectionInterface $connection
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(ConnectionInterface $connection, EventDispatcher $eventDispatcher)
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createTeam(CreateTeamData $data): Team
    {
        return tap(new Team(), function (Team $team) use ($data) {
            $team->team_name = $data->team_name;
            $team->monthly_goal_amount = $data->monthly_goal_amount;

            $this->connection->transaction(fn() => $team->save());

            $this->eventDispatcher->dispatch(
                new TeamCreated($team)
            );
        });
    }

    public function updateTeam(UpdateTeamData $data, Team $team): Team
    {
        return tap($team, function (Team $team) use ($data) {
            $team->team_name = $data->team_name;
            $team->monthly_goal_amount = $data->monthly_goal_amount;

            $this->connection->transaction(fn() => $team->save());

            $this->eventDispatcher->dispatch(
                new TeamUpdated($team)
            );
        });
    }

    public function deleteTeam(Team $team): void
    {
        $this->connection->transaction(fn() => $team->delete());

        $this->eventDispatcher->dispatch(
            new TeamDeleted($team)
        );
    }
}
