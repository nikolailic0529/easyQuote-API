<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserActivityService
{
    public static function userActivityCacheKey(string $id)
    {
        return "activity-user:$id";
    }

    public static function getUserActivity(string $id)
    {
        $activity = Cache::get(static::userActivityCacheKey($id));

        if (is_null($activity)) {
            return Carbon::now();
        }

        return Carbon::parse($activity);
    }

    public static function updateUserActivity(string $id, Carbon $time = null): void
    {
        $time ??= Carbon::now();

        Cache::put(static::userActivityCacheKey($id), $time->toDateTimeString());
    }

    public static function userHasRecentActivity(string $id): bool
    {
        return User::activityExpiresAt()->lt(static::getUserActivity($id));
    }

    public function logoutInactive(): int
    {
        $query = User::where('already_logged_in', true)->toBase();

        $loggedOut = 0;
        $ids = $query->pluck('id');

        if (empty($ids)) {
            return $loggedOut;
        }

        foreach ($ids as $id) {
            $lock = UserRepository::lock($id);

            if (! static::userHasRecentActivity($id)) {
                $lock->get(
                    fn () => DB::transaction(fn () => (clone $query)->where('id', $id)->update(['already_logged_in' => false]))
                );

                $loggedOut++;
            }
        }

        return $loggedOut;
    }
}
