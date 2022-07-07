<?php

namespace App\Services\Auth;

use App\Models\User;

class UserTeamGate
{
    protected array $ledTeamUserCache = [];

    public function isUserLedByUser(?string $ledUserKey, User $user): bool
    {
        if (is_null($ledUserKey)) {
            return false;
        }

        $ledTeamUserDictionary = $this->getLedTeamUserDictionary($user);

        return isset($ledTeamUserDictionary[$ledUserKey]);
    }

    private function getLedTeamUserDictionary(User $user): array
    {
        return $this->ledTeamUserCache[$user->getKey()] ??= value(static function () use ($user): array {
            $userKeys = $user->ledTeamUsers()->pluck($user->ledTeamUsers()->getQualifiedForeignKeyName())->all();

            return array_fill_keys($userKeys, true);
        });
    }
}
