<?php

namespace App\Traits\User;

use App\Services\UserActivityService;
use Illuminate\Support\Carbon;

trait PerformsActivity
{
    public static int $activityExpiresIn; // minutes

    protected static function bootPerformsActivity()
    {
        static::$activityExpiresIn = config('activity.expires_in', 60);
    }

    public function initializePerformsActivity()
    {
        $this->dates = array_merge($this->dates, ['logged_in_at']);
    }

    public function freshActivity(Carbon $time = null): void
    {
        UserActivityService::updateUserActivity($this->getKey(), $time);
    }

    public function getLastActivity(): Carbon
    {
        return UserActivityService::getUserActivity($this->getKey());
    }

    public function getActivityCacheKey()
    {
        return UserActivityService::userActivityCacheKey($this->getKey());
    }

    public function freshLoggedIn(): bool
    {
        return $this->withoutEvents(function () {
            $usesTimestamps = $this->usesTimestamps();
            $this->timestamps = false;

            return tap($this->forceFill(['logged_in_at' => Carbon::now()])->saveOrFail(), fn () => $this->timestamps = $usesTimestamps);
        });
    }

    public static function activityExpiresAt(): Carbon
    {
        return Carbon::now()->subMinutes(static::$activityExpiresIn);
    }

    public function hasRecentActivity(): bool
    {
        return UserActivityService::userHasRecentActivity($this->getKey());
    }

    public function doesntHaveRecentActivity(): bool
    {
        return !$this->hasRecentActivity();
    }
}
