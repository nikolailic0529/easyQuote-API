<?php

namespace App\Domain\User\Services;

use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Image\Models\Image;
use App\Domain\Image\Services\ThumbnailService;
use App\Domain\Invitation\DataTransferObjects\RegisterUserData;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\Notification\DataTransferObjects\NotificationSettings\NotificationSettingsData;
use App\Domain\Notification\DataTransferObjects\UpdateNotificationSettingsControlData;
use App\Domain\Notification\DataTransferObjects\UpdateNotificationSettingsGroupData;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\DataTransferObjects\UpdateCurrentUserData;
use App\Domain\User\DataTransferObjects\UpdateUserData;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Response;
use Spatie\LaravelData\Optional;
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
            if ($data->picture instanceof \SplFileInfo) {
                $user->image()->associate(
                    $this->createPictureForUser($user, $data->picture)
                );
            } elseif (true === $data->delete_picture) {
                $user->image()->disassociate();
            }

            $user->forceFill($data->except(
                'picture', 'delete_picture', 'password', 'current_password', 'change_password',
                'notification_settings',
            )->all());

            if (true === $data->change_password) {
                $user->password = $this->hasher->make($data->password);
            }

            if (!$data->notification_settings instanceof Optional) {
                $user->notification_settings = $this->mapNotificationSettings($data->notification_settings);
            }

            $this->connection->transaction(static function () use ($user): void {
                $user->save();
            });
        });
    }

    public function updateUser(User $user, UpdateUserData $data): User
    {
        return tap($user, function (User $user) use ($data): void {
            if ($data->picture instanceof \SplFileInfo) {
                $user->image()->associate(
                    $this->createPictureForUser($user, $data->picture)
                );
            } elseif (true === $data->delete_picture) {
                $user->image()->disassociate();
            }

            $user->forceFill($data->except('picture', 'delete_picture', 'sales_units', 'companies', 'role_id')->all());

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

    /**
     * @param  iterable<UpdateNotificationSettingsGroupData>  $data
     */
    protected function mapNotificationSettings(iterable $data): NotificationSettingsData
    {
        $settings = [];

        foreach ($data as $group) {
            $settings[$group->key] = $group->controls->toCollection()
                ->keyBy(static function (UpdateNotificationSettingsControlData $data): string {
                    return $data->key;
                })
                ->toArray();
        }

        return NotificationSettingsData::from($settings);
    }

    protected function createPictureForUser(User $user, \SplFileInfo $file): Image
    {
        return $this->thumbnailService->createResizedImageFor($file, $user, [
            'width' => 120,
            'height' => 120,
        ]);
    }

    public static function determineInvitationExpired(Invitation $invitation): bool
    {
        if (is_null($invitation->expires_at) || $invitation->expires_at?->lt(now())) {
            return true;
        }

        return false;
    }
}
