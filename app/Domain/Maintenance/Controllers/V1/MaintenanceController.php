<?php

namespace App\Domain\Maintenance\Controllers\V1;

use App\Domain\Build\Contracts\BuildRepositoryInterface as Builds;
use App\Domain\Maintenance\Jobs\StopMaintenance;
use App\Domain\Maintenance\Jobs\{UpMaintenance};
use App\Domain\Maintenance\Requests\StartMaintenanceRequest;
use App\Foundation\Http\Controller;

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
            \App\Domain\Maintenance\Resources\V1\MaintenanceResource::make($this->builds->last())
        );
    }

    /**
     * Put the application into maintenance mode.
     *
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
            \App\Domain\Maintenance\Resources\V1\MaintenanceResource::make($this->builds->last())
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
