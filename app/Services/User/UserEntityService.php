<?php

namespace App\Services\User;

use App\DTO\Invitation\RegisterUserData;
use App\DTO\User\UpdateCurrentUserData;
use App\DTO\User\UpdateUserData;
use App\Models\Collaboration\Invitation;
use App\Models\Company;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\User;
use App\Services\Image\ThumbnailService;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Response;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserEntityService
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected Hasher $hasher,
        protected ValidatorInterface $validator,
        protected ThumbnailService $thumbnailService
    ) {
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
            $user->team()->associate($invitation->team()->getParentKey());
            $user->setRelation('salesUnits', $invitation->salesUnits()->get());
            $user->setRelation('companies', $invitation->companies()->get());

            $user->first_name = $userData->first_name;
            $user->middle_name = $userData->middle_name;
            $user->last_name = $userData->last_name;
            $user->phone = $userData->phone;
            $user->timezone()->associate($userData->timezone_id);
            $user->password = $this->hasher->make($userData->password);

            $this->connection->transaction(static function () use ($invitation, $user): void {
                $user->save();
                $user->salesUnits()->attach($user->salesUnits);
                $user->companies()->attach($user->companies);
                $user->syncRoles($invitation->role);
            });

            $this->connection->transaction(static function () use ($invitation): void {
                $invitation->delete();
            });
        });
    }

    public function updateCurrentUser(User $user, UpdateCurrentUserData $data): User
    {
        return tap($user, function (User $user) use ($data): void {
            if ($data->picture instanceof \SplFileInfo && true !== $data->delete_picture) {
                $this->thumbnailService->createResizedImageFor($data->picture, $user, [
                    'width' => 120,
                    'height' => 120,
                ]);

                $user->image()->flushQueryCache();
                $user->load('image');
            } elseif (true === $data->delete_picture && null !== $user->image) {
                $user->image->delete();
                $user->image()->flushQueryCache();
                $user->load('image');
            }

            $user->forceFill($data->except('picture', 'delete_picture', 'password', 'current_password', 'change_password')->all());

            if (true === $data->change_password) {
                $user->password = $this->hasher->make($data->password);
            }

            $this->connection->transaction(static function () use ($user): void {
                $user->save();
            });
        });
    }

    public function updateUser(User $user, UpdateUserData $data): User
    {
        return tap($user, function (User $user) use ($data): void {
            $user->forceFill($data->except('sales_units', 'companies', 'role_id')->all());
            $user->setRelation('salesUnits',
                SalesUnit::query()->findMany($data->sales_units->toCollection()->pluck('id'))
            );
            $user->setRelation('companies',
                Company::query()->findMany($data->companies->toCollection()->pluck('id'))
            );
            $role = Role::query()->findOrFail($data->role_id);

            $this->connection->transaction(static function () use ($user, $role): void {
                $user->save();
                $user->salesUnits()->sync($user->salesUnits);
                $user->companies()->sync($user->companies);
                $user->syncRoles($role);
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