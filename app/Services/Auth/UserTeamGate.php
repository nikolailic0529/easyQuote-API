<?php

namespace App\Services\Auth;

use App\Models\User;

class UserTeamGate
{
    protected array $ledTeamUserCache = [];

    public function isUserLedByUser(string $ledUserKey, User $user): bool
    {
        $ledTeamUserDictionary = $this->getLedTeamUserDictionary($user);

        return isset($ledTeamUserDictionary[$ledUserKey]);
    }

    public function getLedTeamUserDictionary(User $user): array
    {
        return $this->ledTeamUserCache[$user->getKey()] ??= value(function () use ($user) {
            $userKeys = $user->ledTeamUsers()->pluck($user->ledTeamUsers()->getQualifiedForeignKeyName())->all();

            return array_fill_keys($userKeys, true);
        });
    }
}
