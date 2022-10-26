<?php

namespace App\Http\Controllers\API\V1;

use App\DTO\Pipeliner\AggregateSyncEventData;
use App\DTO\Pipeliner\BatchArchiveSyncErrorData;
use App\DTO\Pipeliner\BatchRestoreSyncErrorData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeliner\QueuePipelinerSync;
use App\Http\Requests\Pipeliner\SyncModel;
use App\Http\Resources\Pipeliner\PipelinerSyncErrorListResource;
use App\Http\Resources\Pipeliner\PipelinerSyncErrorResource;
use App\Models\Pipeliner\PipelinerSyncError;
use App\Queries\AppEventQueries;
use App\Queries\PipelinerSyncErrorQueries;
use App\Services\Pipeliner\PipelinerDataSyncService;
use App\Services\Pipeliner\PipelinerSyncErrorEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\LaravelData\CursorPaginatedDataCollection;

class PipelinerController extends Controller
{
    /**
     * Paginate sync errors.
     *
     * @param  Request  $request
     * @param  PipelinerSyncErrorQueries  $queries
     * @return AnonymousResourceCollection
     */
    public function paginateSyncErrors(
        Request $request,
        PipelinerSyncErrorQueries $queries
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', PipelinerSyncError::class);

        return PipelinerSyncErrorListResource::collection(
            $queries->baseSyncErrorsQuery($request)->apiPaginate()
        );
    }

    /**
     * Show sync error
     *
     * @param  Request  $request
     * @param  PipelinerSyncError  $error
     * @return PipelinerSyncErrorResource
     */
    public function showSyncError(
        Request $request,
        PipelinerSyncError $error
    ): PipelinerSyncErrorResource {
        $this->authorize('view', $error);

        return PipelinerSyncErrorResource::make($error);
    }

    /**
     * Archive sync error.
     *
     * @param  Request  $request
     * @param  PipelinerSyncErrorEntityService  $service
     * @param  PipelinerSyncError  $error
     * @return Response
     * @throws AuthorizationException
     */
    public function archiveSyncError(
        Request $request,
        PipelinerSyncErrorEntityService $service,
        PipelinerSyncError $error
    ): Response {
        $this->authorize('archive', $error);

        $service->markSyncErrorArchived($error);

        return response()->noContent();
    }

    /**
     * Batch archive sync error.
     *
     * @param  BatchArchiveSyncErrorData  $data
     * @param  PipelinerSyncErrorEntityService  $service
     * @return Response
     */
    public function batchArchiveSyncError(
        BatchArchiveSyncErrorData $data,
        PipelinerSyncErrorEntityService $service
    ): Response {
        $service->batchMarkSyncErrorArchived($data);

        return response()->noContent();
    }

    /**
     * Archive all sync errors.
     */
    public function archiveAllSyncErrors(
        PipelinerSyncErrorEntityService $service
    ): Response {
        $this->authorize('archiveAny', PipelinerSyncError::class);

        $service->markAllSyncErrorsArchived();

        return response()->noContent();
    }

    /**
     * Restore sync error from archive.
     *
     * @param  Request  $request
     * @param  PipelinerSyncErrorEntityService  $service
     * @param  PipelinerSyncError  $error
     * @return Response
     * @throws AuthorizationException
     */
    public function restoreSyncError(
        Request $request,
        PipelinerSyncErrorEntityService $service,
        PipelinerSyncError $error
    ): Response {
        $this->authorize('restoreFromArchive', $error);

        $service->markSyncErrorNotArchived($error);

        return response()->noContent();
    }

    /**
     * Batch restore sync error from archive.
     *
     * @param  BatchRestoreSyncErrorData  $data
     * @param  PipelinerSyncErrorEntityService  $service
     * @return Response
     */
    public function batchRestoreSyncError(
        BatchRestoreSyncErrorData $data,
        PipelinerSyncErrorEntityService $service
    ): Response {
        $service->batchMarkSyncErrorNotArchived($data);

        return response()->noContent();
    }

    /**
     * Restore all sync errors from archive.
     */
    public function restoreAllSyncErrors(
        PipelinerSyncErrorEntityService $service
    ): Response {
        $this->authorize('restoreAnyFromArchive', PipelinerSyncError::class);

        $service->markAllSyncErrorNotArchived();

        return response()->noContent();
    }

    /**
     * Sync particular entity.
     *
     * @param  SyncModel  $request
     * @param  PipelinerDataSyncService  $service
     * @return JsonResponse
     */
    public function syncModel(
        SyncModel $request,
        PipelinerDataSyncService $service
    ): JsonResponse {
        $result = $service
            ->setCauser($request->user())
            ->queueModelSync($request->getModel());

        return response()->json($result);
    }

    /**
     * Queue pipeliner synchronization.
     *
     * @param  QueuePipelinerSync  $request
     * @param  PipelinerDataSyncService  $service
     * @return JsonResponse
     */
    public function queuePipelinerSync(QueuePipelinerSync $request, PipelinerDataSyncService $service): JsonResponse
    {
        $result = $service
            ->setCauser($request->user())
            ->queueSync($request->getStrategies());

        return response()->json($result);
    }

    /**
     * Show pipeliner synchronization status.
     *
     * @param  Request  $request
     * @param  PipelinerDataSyncService  $service
     * @return JsonResponse
     */
    public function showPipelinerSyncStatus(Request $request, PipelinerDataSyncService $service): JsonResponse
    {
        $status = $service
            ->setCauser($request->user())
            ->getDataSyncStatus();

        return response()->json($status);
    }

    /**
     * Show pipeliner synchronization statistics.
     *
     * @param  Request  $request
     * @param  AppEventQueries  $queries
     * @param  PipelinerDataSyncService  $service
     * @return CursorPaginatedDataCollection
     */
    public function showPipelinerSyncStatistics(
        Request $request,
        AppEventQueries $queries,
        PipelinerDataSyncService $service
    ): CursorPaginatedDataCollection {
        $events = $queries->appEventWithPayloadOfQuery('pipeliner-aggregate-sync-completed')->cursorPaginate();

        return AggregateSyncEventData::collection($events);
    }
}