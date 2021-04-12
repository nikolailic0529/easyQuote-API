<?php

namespace App\Services\Activity;

use App\Jobs\CreateActivity;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository;
use Spatie\Activitylog\ActivityLogger as SpatieLogger;
use Spatie\Activitylog\ActivityLogStatus;

class ActivityLogger extends SpatieLogger
{
    private BusDispatcher $busDispatcher;

    public function __construct(AuthManager $auth, Repository $config, BusDispatcher $busDispatcher, ActivityLogStatus $logStatus)
    {
        parent::__construct($auth, $config, $logStatus);

        $this->busDispatcher = $busDispatcher;
    }

    public function causedByService(string $causer): self
    {
        $this->activity->causer_id = null;
        $this->activity->causer_type = null;
        $this->activity->causer_service = $causer;

        return $this;
    }

    public function queue(string $description)
    {
        if ($this->logStatus->disabled()) {
            return;
        }

        $activity = $this->activity;

        $activity->description = $this->replacePlaceholders(
            $activity->description ?? $description,
            $activity
        );

        $activity->created_at = now();

        $this->busDispatcher->dispatch(new CreateActivity($activity));

        $this->activity = null;

        return $activity;
    }
}
