<?php

namespace App\Domain\Team\Services;

use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Team\DataTransferObjects\CreateTeamData;
use App\Domain\Team\DataTransferObjects\UpdateTeamData;
use App\Domain\Team\Events\TeamCreated;
use App\Domain\Team\Events\TeamDeleted;
use App\Domain\Team\Events\TeamUpdated;
use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
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
            ->transaction(static fn () => $team->delete());

        $this->eventDispatcher->dispatch(
            new TeamDeleted($team)
        );
    }
}
