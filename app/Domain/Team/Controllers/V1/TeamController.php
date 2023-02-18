<?php

namespace App\Domain\Team\Controllers\V1;

use App\Domain\Team\DataTransferObjects\CreateTeamData;
use App\Domain\Team\DataTransferObjects\UpdateTeamData;
use App\Domain\Team\Models\Team;
use App\Domain\Team\Queries\TeamQueries;
use App\Domain\Team\Resources\V1\TeamList;
use App\Domain\Team\Resources\V1\TeamWithIncludes;
use App\Domain\Team\Services\TeamEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TeamController extends Controller
{
    protected TeamEntityService $entityService;

    public function __construct(TeamEntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    /**
     * Paginate the existing Team entities.
     *
     * @throws AuthorizationException
     */
    public function paginateTeams(Request $request, TeamQueries $teamQueries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Team::class);

        $paginator = $teamQueries->paginateTeamsQuery($request)->apiPaginate();

        return TeamList::collection($paginator);
    }

    /**
     * Show list of the existing Team entities.
     *
     * @throws AuthorizationException
     */
    public function showListOfTeams(Request $request, TeamQueries $teamQueries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Team::class);

        $paginator = $teamQueries->listingQuery()->get();

        return TeamList::collection($paginator);
    }

    /**
     * Store a newly created Team entity.
     *
     * @throws AuthorizationException
     */
    public function storeTeam(CreateTeamData $data): JsonResponse
    {
        $this->authorize('create', Team::class);

        $resource = $this->entityService->createTeam($data);

        return response()->json(
            $resource,
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the existing Team entity.
     *
     * @throws AuthorizationException
     */
    public function updateTeam(UpdateTeamData $data, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $resource = $this->entityService->updateTeam($data, $team);

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Show the existing Team entity.
     *
     * @throws AuthorizationException
     */
    public function showTeam(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        return response()->json(
            TeamWithIncludes::make($team),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the existing Team entity.
     *
     * @throws AuthorizationException
     */
    public function deleteTeam(Team $team): Response
    {
        $this->authorize('delete', $team);

        $this->entityService->deleteTeam($team);

        return response()->noContent();
    }
}
