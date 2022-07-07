<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeliner\QueuePipelinerSync;
use App\Http\Requests\Pipeliner\SyncModel;
use App\Services\Pipeliner\PipelinerDataSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PipelinerController extends Controller
{
    /**
     * Sync particular entity.
     *
     * @param SyncModel $request
     * @param PipelinerDataSyncService $service
     * @return JsonResponse
     */
    public function syncModel(SyncModel                $request,
                              PipelinerDataSyncService $service): JsonResponse
    {
        $result = $service->syncModel($request->getModel());

        return response()->json($result);
    }

    /**
     * Queue pipeliner synchronization.
     *
     * @param QueuePipelinerSync $request
     * @param PipelinerDataSyncService $service
     * @return JsonResponse
     */
    public function queuePipelinerSync(QueuePipelinerSync $request, PipelinerDataSyncService $service): JsonResponse
    {
        $result = $service
            ->setCauser($request->user())
            ->queueSync();

        return response()->json($result);
    }

    /**
     * Show pipeliner synchronization status.
     *
     * @param Request $request
     * @param PipelinerDataSyncService $service
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