<?php

namespace App\Services;

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
}
