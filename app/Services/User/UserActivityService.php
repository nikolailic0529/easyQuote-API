<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Carbon;


class UserActivityService
{
    public function __construct(protected Cache  $cache,
                                protected Config $config)
    {
    }

    private static function userActivityCacheKey(string $id): string
    {
        return "user-activity::$id";
    }

    protected function getActivityExpiresAtTime(): Carbon
    {
        return Carbon::now()->subMinutes($this->config->get('activity.expires_in', 60));
    }

    public function getActivityTimeOfUser(User $user): Carbon
    {
        $activity = $this->cache->get(self::userActivityCacheKey($user->getKey()));

        if (is_null($activity)) {
            return Carbon::now();
        }

        return Carbon::parse($activity);
    }

    public function updateActivityTimeOfUser(User $user, Carbon $time = null): void
    {
        $time ??= Carbon::now();

        $this->cache->put(self::userActivityCacheKey($user->getKey()), $time->toDateTimeString());
    }

    public function userHasRecentActivity(User $user): bool
    {
        return $this->getActivityExpiresAtTime()
            ->lt($this->getActivityTimeOfUser($user));
    }
}
