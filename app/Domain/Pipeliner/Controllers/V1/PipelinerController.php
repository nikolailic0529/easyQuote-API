<?php

namespace App\Domain\Pipeliner\Controllers\V1;

use App\Domain\AppEvent\Queries\AppEventQueries;
use App\Domain\Pipeliner\DataTransferObjects\AggregateSyncEventData;
use App\Domain\Pipeliner\DataTransferObjects\BatchArchiveSyncErrorData;
use App\Domain\Pipeliner\DataTransferObjects\BatchRestoreSyncErrorData;
use App\Domain\Pipeliner\Models\PipelinerSyncError;
use App\Domain\Pipeliner\Queries\PipelinerSyncErrorQueries;
use App\Domain\Pipeliner\Requests\QueuePipelinerSyncRequest;
use App\Domain\Pipeliner\Requests\SyncModelRequest;
use App\Domain\Pipeliner\Resources\V1\PipelinerSyncErrorListResource;
use App\Domain\Pipeliner\Resources\V1\PipelinerSyncErrorResource;
use App\Domain\Pipeliner\Services\PipelinerDataSyncService;
use App\Domain\Pipeliner\Services\PipelinerSyncErrorEntityService;
use App\Foundation\Http\Controller;
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
     * Show sync error.
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
     */
    public function syncModel(
        SyncModelRequest $request,
        PipelinerDataSyncService $service
    ): JsonResponse {
        $result = $service
            ->setCauser($request->user())
            ->queueModelSync($request->getModel());

        return response()->json($result);
    }

    /**
     * Queue pipeliner synchronization.
     */
    public function queuePipelinerSync(QueuePipelinerSyncRequest $request, PipelinerDataSyncService $service): JsonResponse
    {
        $result = $service
            ->setCauser($request->user())
            ->queueSync($request->getStrategies());

        return response()->json($result);
    }

    /**
     * Show pipeliner synchronization status.
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
     */
    public function showPipelinerSyncStatistics(
        Request $request,
        AppEventQueries $queries,
        PipelinerDataSyncService $service
    ): CursorPaginatedDataCollection {
        $events = $queries->appEventWithPayloadOfQuery('pipeliner-aggregate-sync-completed')->cursorPaginate();

        return AggregateSyncEventData::collection($events);
    }

    /**
     * Show pipeliner in synchronization queue counts.
     */
    public function showPipelinerSyncQueueCounts(PipelinerDataSyncService $service): JsonResponse
    {
        $counts = $service->getQueueCounts();

        return response()->json($counts);
    }
}
