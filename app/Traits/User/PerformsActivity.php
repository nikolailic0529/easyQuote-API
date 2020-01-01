<?php

namespace App\Traits\User;

use Carbon\Carbon;
use Carbon\CarbonInterval;

trait PerformsActivity
{
    static $activityExpiresIn;

    static $refreshActivityExpiresIn;

    public function initializePerformsActivity()
    {
        $this->fillable = array_merge($this->fillable, ['last_activity_at']);
        $this->dates = array_merge($this->dates, ['last_activity_at', 'logged_in_at']);

        self::$activityExpiresIn = config('activity.expires_in', 60);
        self::$refreshActivityExpiresIn = config('activity.refresh_expires_in', 50);
    }

    public function freshActivity(): bool
    {
        /**
         * Perform Activity only if the last User's activity expires earlier than the specified time in minutes.
         */
        if ($this->lastActivityExpiresIn()->minutes > self::$refreshActivityExpiresIn) {
            return false;
        }

        return $this->forceFill(['last_activity_at' => now()])->update();
    }

    public function setLastActivityAt(Carbon $time): bool
    {
        return $this->forceFill(['last_activity_at' => $time])->update();
    }

    public function freshLoggedIn(): bool
    {
        return $this->forceFill(['logged_in_at' => now()])->update();
    }

    public function activityExpiresAt(): Carbon
    {
        return now()->subMinutes(self::$activityExpiresIn);
    }

    public function lastActivityExpiresIn(): CarbonInterval
    {
        return $this->hasRecentActivity()
            ? $this->activityExpiresAt()->diffAsCarbonInterval($this->last_activity_at)
            : CarbonInterval::create(0);
    }

    public function hasRecentActivity(): bool
    {
        return !is_null($this->last_activity_at) && $this->last_activity_at->gte($this->activityExpiresAt());
    }

    public function doesntHaveRecentActivity(): bool
    {
        return !$this->hasRecentActivity();
    }
}
