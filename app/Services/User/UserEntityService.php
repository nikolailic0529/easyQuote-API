<?php

namespace App\Services\User;

use App\DTO\Invitation\RegisterUserData;
use App\Models\Collaboration\Invitation;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Response;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserEntityService
{
    public function __construct(protected ConnectionInterface $connection,
                                protected Hasher $hasher,
                                protected ValidatorInterface $validator)
    {
    }

    public function registerUser(Invitation $invitation, RegisterUserData $userData): User
    {
        if (self::determineInvitationExpired($invitation)) {
            throw new \RuntimeException(IE_01, Response::HTTP_NOT_ACCEPTABLE);
        }

        $violations = $this->validator->validate($userData);

        count($violations) && throw new ValidationFailedException($userData, $violations);

        return tap(new User(), function (User $user) use ($invitation, $userData) {

            $user->email = $invitation->email;
            $user->team()->associate($invitation->team_id);

            $user->first_name = $userData->first_name;
            $user->middle_name = $userData->middle_name;
            $user->last_name = $userData->last_name;
            $user->phone = $userData->phone;
            $user->timezone()->associate($userData->timezone_id);
            $user->password = $this->hasher->make($userData->password);

            $this->connection->transaction(function () use ($user) {
                $user->save();
            });

            $user->syncRoles($invitation->role);

            $this->connection->transaction(function () use ($invitation) {
                $invitation->delete();
            });

        });
    }

    public static function determineInvitationExpired(Invitation $invitation): bool
    {
        if (is_null($invitation->expires_at) || $invitation->expires_at?->lt(now())) {
            return true;
        }

        return false;
    }
}