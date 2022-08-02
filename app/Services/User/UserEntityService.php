<?php

namespace App\Services\User;

use App\DTO\Enum\DataTransferValueOption;
use App\DTO\Invitation\RegisterUserData;
use App\DTO\MissingValue;
use App\DTO\User\UpdateCurrentUserData;
use App\DTO\User\UpdateUserData;
use App\Models\Collaboration\Invitation;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\User;
use App\Services\Image\ThumbnailService;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\Response;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserEntityService
{
    public function __construct(protected ConnectionInterface $connection,
                                protected Hasher              $hasher,
                                protected ValidatorInterface  $validator,
                                protected ThumbnailService    $thumbnailService)
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
            $user->team()->associate($invitation->team()->getParentKey());
            $user->setRelation('salesUnits', $invitation->salesUnits()->get());

            $user->first_name = $userData->first_name;
            $user->middle_name = $userData->middle_name;
            $user->last_name = $userData->last_name;
            $user->phone = $userData->phone;
            $user->timezone()->associate($userData->timezone_id);
            $user->password = $this->hasher->make($userData->password);

            $this->connection->transaction(static function () use ($invitation, $user): void {
                $user->save();
                $user->salesUnits()->attach($user->salesUnits);
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
            if ($data->picture instanceof \SplFileInfo && false === $data->delete_picture) {
                $this->thumbnailService->createResizedImageFor($data->picture, $user, ['width' => 120,
                    'height' => 120]);

                $user->image()->flushQueryCache();
                $user->load('image');
            } elseif (true === $data->delete_picture && null !== $user->image) {
                $user->image->delete();
                $user->image()->flushQueryCache();
                $user->load('image');
            }

            $attrMap = [];
            $attrMap['first_name'] = $data->first_name;
            $attrMap['middle_name'] = $data->middle_name;
            $attrMap['last_name'] = $data->last_name;
            $attrMap['phone'] = $data->phone;

            foreach ($attrMap as $attr => $value) {
                if ($value !== DataTransferValueOption::Miss) {
                    $user->$attr = $value;
                }
            }

            $relationMap = [];
            $relationMap[$user->timezone()->getRelationName()] = $data->timezone_id;
            $relationMap[$user->country()->getRelationName()] = $data->country_id;
            $relationMap[$user->salesUnits()->getRelationName()] = $data->sales_units;

            foreach ($relationMap as $rel => $value) {
                $relation = $user->$rel();

                if ($value !== DataTransferValueOption::Miss) {
                    if ($relation instanceof BelongsTo) {
                        $relation->associate($value);
                    } elseif ($relation instanceof BelongsToMany) {
                        $user->setRelation($relation->getRelationName(), $relation->getRelated()->newQuery()->whereKey(data_get($value, '*.id'))->get());
                    } else {
                        throw new \RuntimeException("Unexpected relation.");
                    }
                }
            }

            if ($data->change_password) {
                $user->password = $this->hasher->make($data->password);
            }

            $this->connection->transaction(static function () use ($user): void {
                $user->save();
                $user->salesUnits()->sync($user->salesUnits);
            });
        });
    }

    public function updateUser(User $user, UpdateUserData $data): User
    {
        return tap($user, function (User $user) use ($data): void {
            $user->first_name = $data->first_name;
            $user->middle_name = $data->middle_name;
            $user->last_name = $data->last_name;
            $user->phone = $data->phone;
            $user->timezone()->associate($data->timezone_id);
            $user->setRelation('salesUnits', SalesUnit::query()->whereKey(data_get($data->sales_units, '*.id'))->get());
            $user->team()->associate($data->team_id);

            $role = Role::query()->findOrFail($data->role_id);

            $this->connection->transaction(static function () use ($user, $role): void {
                $user->save();
                $user->salesUnits()->sync($user->salesUnits);
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