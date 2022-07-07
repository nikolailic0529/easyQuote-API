<?php

namespace App\Http\Controllers\API\V1\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\ShowActivityLogMetaData;
use App\Http\Requests\System\GetActivitiesRequest;
use App\Http\Resources\V1\ActivityCollection;
use App\Models\System\Activity;
use App\Queries\ActivityQueries;
use App\Services\Activity\ActivityDataExporter;
use App\Services\Activity\ActivityDataMapper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityController extends Controller
{
    /**
     * Paginate the existing activity entities.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @param ActivityDataExporter $dataExporter
     * @param ActivityDataMapper $dataMapper
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function paginateActivities(GetActivitiesRequest $request,
                                       ActivityQueries $queries,
                                       ActivityDataExporter $dataExporter,
                                       ActivityDataMapper $dataMapper): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        $query = $queries->paginateActivitiesQuery($request);

        $collection = $dataMapper->mapActivityLogPaginator($query->apiPaginate());

        return response()->json(ActivityCollection::make(
            $collection
        )
            ->additional(['summary' => $dataExporter->getActivitySummary($query)]));
    }

    /**
     * Display a listing of the Activities in the specified Subject.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @param ActivityDataExporter $dataExporter
     * @param ActivityDataMapper $dataMapper
     * @param string $subject
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function paginateActivitiesOfSubject(GetActivitiesRequest $request,
                                                ActivityQueries $queries,
                                                ActivityDataExporter $dataExporter,
                                                ActivityDataMapper $dataMapper,
                                                string $subject): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        $query = $queries->paginateActivitiesOfSubjectQuery($subject, $request);

        $collection = $dataMapper->mapActivityLogPaginator($query->apiPaginate());

        return response()->json(ActivityCollection::make(
            $collection
        )
            ->additional(['summary' => $dataExporter->getActivitySummary($query), 'subject_name' => $request->getSubjectName()]));
    }

    /**
     * Export activity log to pdf.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @param ActivityDataExporter $dataExporter
     * @return BinaryFileResponse
     * @throws AuthorizationException
     */
    public function exportActivityLogToPdf(GetActivitiesRequest $request,
                                           ActivityQueries $queries,
                                           ActivityDataExporter $dataExporter): BinaryFileResponse
    {

        $this->authorize('viewAny', Activity::class);

        return response()->download(
            $dataExporter->exportToPdf(
                $queries->paginateActivitiesQuery($request)
            )
        );
    }

    /**
     * Export activity log to csv.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @param ActivityDataExporter $dataExporter
     * @return BinaryFileResponse
     * @throws AuthorizationException
     */
    public function exportActivityLogToCsv(GetActivitiesRequest $request,
                                           ActivityQueries $queries,
                                           ActivityDataExporter $dataExporter): BinaryFileResponse
    {

        $this->authorize('viewAny', Activity::class);

        return response()->download(
            $dataExporter->exportToCsv(
                $queries->paginateActivitiesQuery($request)
            )
        );
    }

    /**
     * Export activity log to pdf.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @param ActivityDataExporter $dataExporter
     * @param string $subject
     * @return BinaryFileResponse
     * @throws AuthorizationException
     */
    public function exportActivityLogOfSubjectToPdf(GetActivitiesRequest $request,
                                                    ActivityQueries $queries,
                                                    ActivityDataExporter $dataExporter,
                                                    string $subject): BinaryFileResponse
    {

        $this->authorize('viewAny', Activity::class);

        return response()->download(
            $dataExporter->exportToPdf(
                $queries->paginateActivitiesOfSubjectQuery($subject, $request)
            )
        );
    }

    /**
     * Export activity log to csv.
     *
     * @param GetActivitiesRequest $request
     * @param ActivityQueries $queries
     * @param ActivityDataExporter $dataExporter
     * @param string $subject
     * @return BinaryFileResponse
     * @throws AuthorizationException
     */
    public function exportActivityLogOfSubjectToCsv(GetActivitiesRequest $request,
                                                    ActivityQueries $queries,
                                                    ActivityDataExporter $dataExporter,
                                                    string $subject): BinaryFileResponse
    {

        $this->authorize('viewAny', Activity::class);

        return response()->download(
            $dataExporter->exportToCsv(
                $queries->paginateActivitiesOfSubjectQuery($subject, $request)
            )
        );
    }

    /**
     * Display the meta information for activities filtering.
     *
     * @param ShowActivityLogMetaData $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showActivityLogMetaData(ShowActivityLogMetaData $request): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        return response()->json(
            $request->getMetaData()
        );
    }
}
