<?php

namespace App\Services;

use App\Jobs\CreateActivity;
use Spatie\Activitylog\ActivityLogger as SpatieLogger;

class ActivityLogger extends SpatieLogger
{
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

        CreateActivity::dispatch($activity);

        $this->activity = null;

        return $activity;
    }
}
