<?php

namespace App\Domain\Activity\Services;

use App\Domain\Activity\Jobs\CreateActivity;
use App\Domain\Activity\Models\Activity;
use App\Domain\User\Services\ApplicationUserResolver;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository;
use Spatie\Activitylog\ActivityLogger as SpatieLogger;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\ActivityLogStatus;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class ActivityLogger extends SpatieLogger
{
    public function __construct(
        AuthManager $auth,
        Repository $config,
        ActivityLogStatus $logStatus,
        protected readonly ChangesDetector $changesDetector,
        protected readonly BusDispatcher $busDispatcher,
        protected readonly ApplicationUserResolver $applicationUserResolver,
    ) {
        parent::__construct($auth, $config, $logStatus);
    }

    public function causedByService(string $causer): static
    {
        tap($this->getActivity(), static function (Activity $activity) use ($causer): void {
            $activity->causer()->dissociate();
            $activity->causer_service = $causer;
        });

        return $this;
    }

    public function log(string $description): ?Activity
    {
        if (false === $this->getActivity()->submitEmptyLogs
            && $this->changesDetector->isLogEmpty($this->getActivity()->properties)) {
            return null;
        }

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::log($description);
    }

    public function queue(string $description): ?Activity
    {
        if (false === $this->getActivity()->submitEmptyLogs
            && $this->changesDetector->isLogEmpty($this->getActivity()->properties)) {
            return null;
        }

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

    public function submitEmptyLogs(bool $value = true): static
    {
        tap($this->getActivity(), static function (Activity $activity) use ($value): void {
            $activity->submitEmptyLogs = $value;
        });

        return $this;
    }

    protected function getActivity(): ActivityContract|Activity
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
