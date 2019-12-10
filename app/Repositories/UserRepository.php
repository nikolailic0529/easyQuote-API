<?php

namespace App\Repositories;

use App\Contracts\Repositories\{
    UserRepositoryInterface,
    CountryRepositoryInterface as CountryRepository,
    TimezoneRepositoryInterface as TimezoneRepository
};
use App\Http\Requests\{
    Collaboration\InviteUserRequest,
    Collaboration\UpdateUserRequest,
    PasswordResetRequest as AppPasswordResetRequest,
    StoreResetPasswordRequest,
    UpdateProfileRequest
};
use App\Http\Resources\UserListResource;
use App\Models\{
    User,
    Role,
    Collaboration\Invitation,
    PasswordReset
};
use App\Notifications\{
    PasswordResetRequest,
    PasswordResetSuccess
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};
use Illuminate\Support\Collection as SupportCollection;
use Arr, Hash;

class UserRepository extends SearchableRepository implements UserRepositoryInterface
{
    protected $user;

    protected $role;

    protected $invitation;

    protected $passwordReset;

    protected $country;

    protected $timezone;

    public function __construct(
        User $user,
        Role $role,
        Invitation $invitation,
        PasswordReset $passwordReset,
        CountryRepository $country,
        TimezoneRepository $timezone
    ) {
        $this->user = $user;
        $this->role = $role;
        $this->invitation = $invitation;
        $this->passwordReset = $passwordReset;
        $this->country = $country;
        $this->timezone = $timezone;
    }

    public function userQuery(): Builder
    {
        return $this->user->query();
    }

    public function list()
    {
        $users = $this->userQuery()
            ->where([
                ['first_name', '!=', ''],
                ['last_name', '!=', ''],
                ['email', '!=', ''],
            ])
            ->withTrashed()
            ->get();
        return UserListResource::collection($users);
    }

    public function find(string $id): User
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function data(): SupportCollection
    {
        $roles = $this->role->get(['id', 'name']);
        $countries = $this->country->all();
        $timezones = $this->timezone->all();

        return collect(compact('roles', 'countries', 'timezones'));
    }

    public function make(array $array): User
    {
        return $this->user->make($array);
    }

    public function create(array $attributes): User
    {
        $password = Hash::make($attributes['password']);
        $attributes = array_merge($attributes, compact('password'));

        return $this->user->create($attributes);
    }

    public function createAdministrator(array $attributes): User
    {
        $user = $this->create($attributes);
        $user->assignRole($this->role->administrator());

        return $user;
    }

    public function createCollaborator(array $attributes, Invitation $invitation): User
    {
        $invitation->isExpired && abort(406, __('invitation.expired_exception'));

        $user = $this->create(array_merge($attributes, $invitation->only('email')));
        $user->interact($invitation);

        return $user;
    }

    public function invite(InviteUserRequest $request): bool
    {
        return (bool) $request->user()->invitations()->create($request->validated());
    }

    public function invitation(string $token): Invitation
    {
        $invitation = $this->invitation->whereInvitationToken($token)->firstOrFail()->makeHiddenExcept(['email', 'role_name']);

        $invitation->isExpired && abort(406, __('invitation.expired_exception'));

        return $invitation;
    }

    public function update(UpdateUserRequest $request, string $id): bool
    {
        return $this->find($id)->update($request->validated());
    }

    public function updateOwnProfile(UpdateProfileRequest $request): User
    {
        $user = $request->user();

        $user->createImage($request->picture, ['width' => 120, 'height' => 120]);
        $user->deleteImageWhen($request->delete_picture);

        $attributes = Arr::except($request->validated(), ['password']);

        if ($request->change_password) {
            $password = Hash::make($request->password);
            $attributes = array_merge($attributes, compact('password'));
        }

        $user->update($attributes);
        $user->makeVisible('privileges');

        return $user;
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    public function administrators(): Collection
    {
        return $this->user->administrators()->get();
    }

    public function resetPassword(StoreResetPasswordRequest $request, string $id): bool
    {
        $user = $this->find($id);
        $user_id = $user->id;
        $expires_at = now()->addHours(12)->toDateTimeString();

        $passwordReset = $this->passwordReset->updateOrCreate(
            compact('user_id'),
            array_merge($request->validated(), compact('expires_at'))
        );

        try {
            $user->notify(new PasswordResetRequest($passwordReset));
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function resetAccount(string $id): bool
    {
        return $this->find($id)->markAsLoggedOut();
    }

    public function performResetPassword(AppPasswordResetRequest $request, string $token): bool
    {
        $passwordReset = $this->passwordReset->whereToken($token)->firstOrFail();

        $passwordReset->isExpired && abort(406, __('password_reset.expired_exception'));

        $password = Hash::make($request->password);

        $passwordReset->user->notify(new PasswordResetSuccess);

        return $passwordReset->user->update(compact('password')) && $passwordReset->delete();
    }

    public function verifyPasswordReset(string $token): bool
    {
        $passwordReset = $this->passwordReset->whereToken($token)->first();

        return isset($passwordReset) && !$passwordReset->isExpired;
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\User\OrderByEmail::class,
            \App\Http\Query\User\OrderByName::class,
            \App\Http\Query\User\OrderByFirstname::class,
            \App\Http\Query\User\OrderByLastname::class,
            \App\Http\Query\User\OrderByRole::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->user;
    }

    protected function searchableFields(): array
    {
        return [
            'email^5', 'first_name^4', 'middle_name^4', 'last_name^4', 'role_name^3', 'created_at^3'
        ];
    }

    protected function searchableScope(Builder $query)
    {
        return $query;
    }
}
