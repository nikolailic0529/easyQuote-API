<?php

namespace App\Domain\User\Services;

use App\Domain\Sync\Enum\Lock;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;

class InactiveUserService
{
    public function __construct(protected LockProvider $lockProvider,
                                protected ConnectionInterface $connection,
                                protected UserActivityService $activityService)
    {
    }

    public function logoutInactiveUsers(): int
    {
        /** @var Collection<int, User>|User[] $loggedInUsers */
        $loggedInUsers = User::query()->where('already_logged_in', true)->get();

        $loggedOut = 0;

        if ($loggedInUsers->isEmpty()) {
            return $loggedOut;
        }

        foreach ($loggedInUsers as $user) {
            $lock = $this->lockProvider->lock(Lock::UPDATE_USER($user->getKey()), 10);

            if (false === $this->activityService->userHasRecentActivity($user)) {
                $lock->get(function () use (&$loggedOut, $user) {
                    $this->connection->transaction(fn () => User::query()->whereKey($user->getKey())->toBase()->update(['already_logged_in' => false]));

                    ++$loggedOut;
                });
            }
        }

        return $loggedOut;
    }
}
