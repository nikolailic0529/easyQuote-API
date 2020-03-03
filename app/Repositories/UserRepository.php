<?php

namespace App\Repositories;

use App\Contracts\Repositories\{
    UserRepositoryInterface
};
use App\Http\Requests\{
    Collaboration\UpdateUserRequest,
    PasswordResetRequest as AppPasswordResetRequest,
    StoreResetPasswordRequest,
    UpdateProfileRequest
};
use App\Http\Resources\{
    UserListResource,
    UserRepositoryCollection
};
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
use Arr, Hash;
use Closure;
use Illuminate\Support\LazyCollection;

class UserRepository extends SearchableRepository implements UserRepositoryInterface
{
    protected User $user;

    protected Role $role;

    protected Invitation $invitation;

    protected PasswordReset $passwordReset;

    public function __construct(
        User $user,
        Role $role,
        Invitation $invitation,
        PasswordReset $passwordReset
    ) {
        $this->user = $user;
        $this->role = $role;
        $this->invitation = $invitation;
        $this->passwordReset = $passwordReset;
    }

    public function userQuery(): Builder
    {
        return $this->user->query()->with('roles', 'image');
    }

    public function all()
    {
        return $this->toCollection(parent::all());
    }

    public function search(string $query = '')
    {
        return $this->toCollection(parent::search($query));
    }

    public function listWithTrashed()
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

    public function list(array $columns = ['*'])
    {
        return $this->user->newQueryWithoutRelationships()->get($columns);
    }

    public function cursor(?Closure $scope = null): LazyCollection
    {
        $query = $this->user->query();

        if ($scope instanceof Closure) {
            call_user_func($scope, $query);
        }

        return $query->cursor();
    }

    public function toCollection($resource): UserRepositoryCollection
    {
        return new UserRepositoryCollection($resource);
    }

    public function find(string $id): User
    {
        return $this->userQuery()->whereId($id)->firstOrFail()->withAppends();
    }

    public function findByEmail($email)
    {
        $query = $this->user->query();

        if (is_string($email)) {
            return $query->where('email', 'like', "%{$email}%")->first();
        }

        if (is_array($email)) {
            return $query->whereIn('email', $email)->get();
        }

        throw new \InvalidArgumentException(INV_ARG_SA_01);
    }

    public function findMany(array $ids): Collection
    {
        return $this->user->query()->whereIn('id', $ids)->get();
    }

    public function random(): User
    {
        return $this->user->query()->inRandomOrder()->firstOrFail();
    }

    public function authenticatedIpExists(string $excludedId, string $ip): bool
    {
        return $this->user->query()->whereKeyNot($excludedId)->loggedIn()->ip($ip)->exists();
    }

    public function authenticatedIpDoesntExist(string $excludedId, string $ip): bool
    {
        return !$this->authenticatedIpExists($excludedId, $ip);
    }

    public function make(array $array): User
    {
        return $this->user->make($array);
    }

    public function create(array $attributes): User
    {
        $password = Hash::make($attributes['password']);
        data_set($attributes, 'password', $password);

        return $this->user->create($attributes);
    }

    public function createAdministrator(array $attributes): User
    {
        return tap($this->create($attributes))->assignRole($this->role->administrator());
    }

    public function createCollaborator(array $attributes, Invitation $invitation): User
    {
        error_abort_if($invitation->isExpired, IE_01, 'IE_01', 406);

        $attributes = array_merge($attributes, $invitation->only('email'));

        return tap($this->create($attributes))->interact($invitation);
    }

    public function invite($request): Invitation
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return $this->invitation->create($request);
    }

    public function invitation(string $token): Invitation
    {
        $invitation = $this->invitation->whereInvitationToken($token)->first();

        error_abort_if(is_null($invitation) || $invitation->isExpired, IE_01, 'IE_01', 404);

        $invitation->makeHiddenExcept(['email', 'role_name']);

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

        return $user->withAppends();
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

        error_abort_if($passwordReset->isExpired, PRE_01, 'PRE_01', 406);

        $password = Hash::make($request->password);

        tap($passwordReset->user)->notify(new PasswordResetSuccess)->markAsLoggedOut();

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

    protected function searchableScope($query)
    {
        return $query;
    }
}
