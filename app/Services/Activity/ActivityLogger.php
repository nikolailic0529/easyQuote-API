<?php

namespace App\Services\Activity;

use App\Jobs\CreateActivity;
use App\Services\User\ApplicationUserResolver;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository;
use Spatie\Activitylog\ActivityLogger as SpatieLogger;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\ActivityLogStatus;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class ActivityLogger extends SpatieLogger
{
    public function __construct(
        AuthManager $auth,
        Repository $config,
        ActivityLogStatus $logStatus,
        protected readonly BusDispatcher $busDispatcher,
        protected readonly ApplicationUserResolver $applicationUserResolver,
    ) {
        parent::__construct($auth, $config, $logStatus);
    }

    public function causedByService(string $causer): self
    {
        $this->getActivity()->causer_id = null;
        $this->getActivity()->causer_type = null;
        $this->getActivity()->causer_service = $causer;

        return $this;
    }

    public function queue(string $description): ?Activity
    {
        if ($this->logStatus->disabled()) {
            return null;
        }

        $activity = $this->getActivity();

        $activity->description = $this->replacePlaceholders(
            $activity->description ?? $description,
            $activity
        );

        $activity->updateTimestamps();

        $this->busDispatcher->dispatch(new CreateActivity($activity));

        return tap($activity, function (): void {
            $this->activity = null;
        });
    }

    protected function getActivity(): Activity
    {
        if (!$this->activity instanceof ActivityContract) {
            $this->activity = ActivitylogServiceProvider::getActivityModelInstance();
            $this
                ->useLog($this->defaultLogName)
                ->withProperties([])
                ->causedBy(
                    $this->auth->guard($this->authDriver)->user() ?? $this->applicationUserResolver->resolve()
                );
        }

        return $this->activity;
    }
}
