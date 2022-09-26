<?php

namespace App\Services\Team;

use App\Models\SalesUnit;
use App\Models\User;
use App\DTO\{Team\CreateTeamData, Team\UpdateTeamData};
use App\Events\{Team\TeamCreated, Team\TeamDeleted, Team\TeamUpdated};
use App\Models\Team;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Events\Dispatcher as EventDispatcher;

class TeamEntityService
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver,
        protected readonly EventDispatcher $eventDispatcher
    ) {
    }

    public function createTeam(CreateTeamData $data): Team
    {
        return tap(new Team(), function (Team $team) use ($data): void {
            $team->forceFill($data->except('sales_units', 'team_leaders')->all());

            $teamLeaders = User::query()->findMany($data->team_leaders->toCollection()->pluck('id'));
            $salesUnits = SalesUnit::query()->findMany($data->sales_units->toCollection()->pluck('id'));

            $this->connectionResolver->connection()
                ->transaction(static function () use ($teamLeaders, $salesUnits, $team): void {
                    $team->save();

                    $team->teamLeaders()->attach($teamLeaders);
                    $team->salesUnits()->attach($salesUnits);
                });

            $this->eventDispatcher->dispatch(
                new TeamCreated($team)
            );
        });
    }

    public function updateTeam(UpdateTeamData $data, Team $team): Team
    {
        return tap($team, function (Team $team) use ($data) {
            $team->forceFill($data->except('sales_units', 'team_leaders')->all());

            $teamLeaders = User::query()->findMany($data->team_leaders->toCollection()->pluck('id'));
            $salesUnits = SalesUnit::query()->findMany($data->sales_units->toCollection()->pluck('id'));

            $this->connectionResolver->connection()
                ->transaction(static function () use ($teamLeaders, $salesUnits, $team): void {
                    $team->save();

                    $team->teamLeaders()->sync($teamLeaders);
                    $team->salesUnits()->sync($salesUnits);
                });

            $this->eventDispatcher->dispatch(
                new TeamUpdated($team)
            );
        });
    }

    public function deleteTeam(Team $team): void
    {
        $this->connectionResolver->connection()
            ->transaction(static fn() => $team->delete());

        $this->eventDispatcher->dispatch(
            new TeamDeleted($team)
        );
    }
}
