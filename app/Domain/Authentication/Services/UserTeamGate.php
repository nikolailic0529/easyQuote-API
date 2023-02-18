<?php

namespace App\Domain\Authentication\Services;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserTeamGate
{
    public function isLedByUser(Model|string|null $ledUser, User $teamLeader): bool
    {
        if (is_null($ledUser)) {
            return false;
        }

        if ($ledUser instanceof Model) {
            $ledUser = $ledUser->getKey();
        }

        $ledUsersMap = once(function () use ($teamLeader): Collection {
            if ($teamLeader->relationLoaded('ledTeamUsers')) {
                return $teamLeader->ledTeamUsers
                    ->mapWithKeys(static function (User $user): array {
                        return [$user->getKey() => true];
                    });
            }

            return $teamLeader->ledTeamUsers()
                ->pluck($teamLeader->ledTeamUsers()->getModel()->getQualifiedKeyName())
                ->mapWithKeys(static function (string $id): array {
                    return [$id => true];
                });
        });

        return $ledUsersMap[$ledUser] ?? false;
    }
}
