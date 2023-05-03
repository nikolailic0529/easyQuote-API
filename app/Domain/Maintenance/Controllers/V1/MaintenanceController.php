<?php

namespace App\Domain\Maintenance\Controllers\V1;

use App\Domain\Build\Contracts\BuildRepositoryInterface;
use App\Domain\Maintenance\Jobs\DownIntoMaintenanceMode;
use App\Domain\Maintenance\Jobs\UpFromMaintenanceMode;
use App\Domain\Maintenance\Requests\StartMaintenanceRequest;
use App\Domain\Maintenance\Resources\V1\MaintenanceResource;
use App\Foundation\Http\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MaintenanceController extends Controller
{
    /**
     * Show maintenance status.
     */
    public function show(BuildRepositoryInterface $buildRepository): JsonResponse
    {
        return response()->json(
            MaintenanceResource::make($buildRepository->last())
        );
    }

    /**
     * Begin maintenance.
     */
    public function start(
        StartMaintenanceRequest $request,
        BuildRepositoryInterface $buildRepository,
        Dispatcher $dispatcher,
    ): JsonResponse {
        $request->updateRelatedSettings();

        if ($request->boolean('enable')) {
            $buildRepository->updateLastOrCreate($request->validated());

            $dispatcher->dispatch(
                new DownIntoMaintenanceMode(startTime: $request->carbonStartTime, endTime: $request->carbonEndTime, autocomplete: true)
            );
        }

        return response()->json(
            MaintenanceResource::make($buildRepository->last())
        );
    }

    /**
     * Finish maintenance.
     */
    public function stop(Dispatcher $dispatcher): Response
    {
        $dispatcher->dispatch(new UpFromMaintenanceMode());

        return response()->noContent();
    }
}
