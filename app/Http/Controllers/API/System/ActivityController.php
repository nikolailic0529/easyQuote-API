<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\ShowActivityMeta;
use App\Http\Requests\System\GetActivitiesRequest;
use App\Http\Resources\ActivityCollection;
use App\Models\System\Activity;
use App\Queries\ActivityQueries;
use App\Queries\UserQueries;
use App\Services\Activity\ActivityExporter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Activity::class, 'activity');
    }

    /**
     * Display a listing of the Activities.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @return ActivityCollection
     */
    public function index(GetActivitiesRequest $request, ActivityQueries $queries): ActivityCollection
    {
        $paginator = $queries->paginateActivityQuery($request)->apiPaginate();

        return tap(new ActivityCollection($paginator), function (ActivityCollection $collection) use ($paginator, $queries) {
            $collection->additional([
                'summary' => $queries->activitySummaryQuery()->get(),
            ]);

            $collection->additional($collection->additional + [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'last_page' => $paginator->lastPage(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]);
        });
    }

    /**
     * Show a listing of activity by the specified subject.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @param string $subject
     * @return ActivityCollection
     * @throws AuthorizationException
     */
    public function subject(GetActivitiesRequest $request, ActivityQueries $queries, string $subject): ActivityCollection
    {
        $this->authorize('viewAny', Activity::class);

        $paginator = $queries->paginateActivityBySubjectQuery($subject, $request)->apiPaginate();

        return tap(new ActivityCollection($paginator), function (ActivityCollection $collection) use ($paginator, $subject, $queries) {
            $collection->additional([
                'summary' => $queries->activitySummaryBySubjectQuery($subject)->get(),
            ])->appendSubjectName();

            $collection->additional($collection->additional + [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'last_page' => $paginator->lastPage(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]);
        });
    }

    /**
     * Export a listing of the Activities in specified format.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityExporter $exporter
     * @param string $type
     * @return Response
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function export(GetActivitiesRequest $request, ActivityExporter $exporter, string $type): Response
    {
        $this->authorize('viewAny', Activity::class);

        return $exporter->export($type);
    }

    /**
     * Export a list of the Activities for specified subject in specified format.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityExporter $exporter
     * @param string $subject
     * @param string $type
     * @return Response
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function exportSubject(GetActivitiesRequest $request,
                                  ActivityExporter $exporter,
                                  string $subject,
                                  string $type): Response
    {
        $this->authorize('viewAny', Activity::class);

        return $exporter->exportSubject($type, $subject);
    }

    /**
     * Display the meta information for activities filtering.
     *
     * @param ShowActivityMeta $request
     * @param UserQueries $userQueries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function meta(ShowActivityMeta $request, UserQueries $userQueries): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        return response()->json(
            $request->getActivityMetaData()
        );
    }
}
