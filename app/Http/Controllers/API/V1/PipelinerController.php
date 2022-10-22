<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeliner\QueuePipelinerSync;
use App\Http\Requests\Pipeliner\SyncModel;
use App\Http\Resources\Pipeliner\PipelinerSyncErrorListResource;
use App\Http\Resources\Pipeliner\PipelinerSyncErrorResource;
use App\Models\Pipeliner\PipelinerSyncError;
use App\Queries\PipelinerSyncErrorQueries;
use App\Services\Pipeliner\PipelinerDataSyncService;
use App\Services\Pipeliner\PipelinerSyncErrorEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

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
     */
    public function archiveSyncError(
        Request $request,
        PipelinerSyncErrorEntityService $service,
        PipelinerSyncError $error
    ): Response {
        $this->authorize('archive', $error);

        $service->archiveSyncError($error);

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
        $result = $service->syncModel($request->getModel());

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
}