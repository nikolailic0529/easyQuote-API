<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StartMaintenanceRequest;
use App\Jobs\{
    StopMaintenance,
    UpMaintenance
};
use App\Contracts\Repositories\System\BuildRepositoryInterface as Builds;
use App\Http\Resources\System\MaintenanceResource;

class MaintenanceController extends Controller
{
    /** @var \App\Contracts\Repositories\System\BuildRepositoryInterface */
    protected Builds $builds;

    public function __construct(Builds $builds)
    {
        $this->builds = $builds;
    }

    /**
     * Display an information about the current maintenance.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        return response()->json(
            MaintenanceResource::make($this->builds->latest())
        );
    }

    /**
     * Put the application into maintenance mode.
     *
     * @param  \App\Http\Requests\Maintenance\StartMaintenanceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function start(StartMaintenanceRequest $request)
    {
        $request->updateRelatedSettings();

        if ($request->enable) {
            $this->builds->updateLatestOrCreate($request->validated());
            UpMaintenance::dispatchAfterResponse($request->carbonStartTime, $request->carbonEndTime, true);
        }

        return response()->json(
            MaintenanceResource::make($this->builds->latest())
        );
    }

    /**
     * Bring the application out of maintenance mode.
     *
     * @return \Illuminate\Http\Response
     */
    public function stop()
    {
        StopMaintenance::dispatchAfterResponse();

        return response()->json(true);
    }
}
