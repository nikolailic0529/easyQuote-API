<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Team\TeamWithIncludes;
use App\Http\Requests\{Team\CreateTeam, Team\UpdateTeam};
use App\Http\Resources\Team\TeamList;
use App\Models\Team;
use App\Queries\TeamQueries;
use App\Services\Team\TeamEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\AnonymousResourceCollection, Response};

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
     * @param Request $request
     * @param TeamQueries $teamQueries
     * @return AnonymousResourceCollection
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
     * @param Request $request
     * @param TeamQueries $teamQueries
     * @return AnonymousResourceCollection
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
     * @param CreateTeam $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeTeam(CreateTeam $request): JsonResponse
    {
        $this->authorize('create', Team::class);

        $resource = $this->entityService->createTeam($request->getCreateTeamData());

        return response()->json(
            $resource,
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the existing Team entity.
     *
     * @param UpdateTeam $request
     * @param Team $team
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateTeam(UpdateTeam $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $resource = $this->entityService->updateTeam($request->getUpdateTeamData(), $team);

        return response()->json(
            $resource,
            Response::HTTP_OK
        );
    }

    /**
     * Show the existing Team entity.
     *
     * @param Team $team
     * @return JsonResponse
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
     * @param Team $team
     * @return Response
     * @throws AuthorizationException
     */
    public function deleteTeam(Team $team): Response
    {
        $this->authorize('delete', $team);

        $this->entityService->deleteTeam($team);

        return response()->noContent();
    }
}
