<?php

namespace App\Http\Controllers\API\V1\System;

use App\Contracts\Repositories\System\BuildRepositoryInterface as Builds;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StartMaintenanceRequest;
use App\Jobs\{StopMaintenance, UpMaintenance};

class MaintenanceController extends Controller
{
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
            \App\Http\Resources\V1\System\MaintenanceResource::make($this->builds->last())
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
            $this->builds->updateLastOrCreate($request->validated());
            
            UpMaintenance::dispatchAfterResponse($request->carbonStartTime, $request->carbonEndTime, true);
        }

        return response()->json(
            \App\Http\Resources\V1\System\MaintenanceResource::make($this->builds->last())
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
