<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;

class InactiveUsersService
{
    protected UserRepositoryInterface $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function logoutInactive(): int
    {
        $time = now()->subMinutes(config('activity.expires_in', 60));

        $ids = $this->users->pluckWhere([['last_activity_at', '<=', $time], ['already_logged_in', '=', true]], 'id');

        if (empty($ids)) {
            return 0;
        }

        foreach ($ids as $id) {
            $this->users->updateWhere(['already_logged_in' => false], ['id' => $id]);
        }

        return count($ids);
    }
}
