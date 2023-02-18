<?php

namespace App\Domain\Activity\Controllers\V1;

use App\Domain\Activity\Models\Activity;
use App\Domain\Activity\Queries\ActivityQueries;
use App\Domain\Activity\Requests\GetActivitiesRequest;
use App\Domain\Activity\Requests\ShowActivityLogMetaDataRequest;
use App\Domain\Activity\Resources\V1\ActivityCollection;
use App\Domain\Activity\Services\ActivityDataExporter;
use App\Domain\Activity\Services\ActivityDataMapper;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityController extends Controller
{
    /**
     * Paginate the existing activity entities.
     *
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

        return response()->json(\App\Domain\Activity\Resources\V1\ActivityCollection::make(
            $collection
        )
            ->additional(['summary' => $dataExporter->getActivitySummary($query), 'subject_name' => $request->getSubjectName()]));
    }

    /**
     * Export activity log to pdf.
     *
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
     * @throws AuthorizationException
     */
    public function exportActivityLogToCsv(GetActivitiesRequest $request,
                                           ActivityQueries $queries,
                                           ActivityDataExporter $dataExporter): BinaryFileResponse
    {
        $this->authorize('viewAny', \App\Domain\Activity\Models\Activity::class);

        return response()->download(
            $dataExporter->exportToCsv(
                $queries->paginateActivitiesQuery($request)
            )
        );
    }

    /**
     * Export activity log to pdf.
     *
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
     * @throws AuthorizationException
     */
    public function showActivityLogMetaData(ShowActivityLogMetaDataRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        return response()->json(
            $request->getMetaData()
        );
    }
}
