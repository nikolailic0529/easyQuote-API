<?php namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Http\Requests \ {
    Collaboration\InviteUserRequest,
    Collaboration\UpdateUserRequest,
    UserSignUpRequest
};
use App\Models \ {
    User,
    Role,
    Collaboration\Invitation
};
use App\Builder\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Hash;

class UserRepository implements UserRepositoryInterface
{
    protected $user;

    protected $role;

    protected $invitation;

    public function __construct(User $user, Role $role, Invitation $invitation)
    {
        $this->user = $user;
        $this->role = $role;
        $this->invitation = $invitation;
    }

    public function userQuery(): Builder
    {
        //
    }

    public function find(string $id): User
    {
        //
    }

    public function all(): Paginator
    {
        //
    }

    public function search(string $query = ''): Paginator
    {
        //
    }

    public function data(): Collection
    {
        $roles = $this->role->userCollaboration()->get(['id', 'name']);

        return collect(compact('roles'));
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

    public function completeInvitation(array $attributes, Invitation $invitation): User
    {
        $user = $this->create($attributes);
        $user->interact($invitation);

        return $user;
    }

    public function invite(InviteUserRequest $request): bool
    {
        return (bool) $request->user()->invitations()->create($request->validated());
    }

    public function invitation(string $token): Invitation
    {
        return $this->invitation->whereInvitationToken($token)->firstOrFail()->makeHiddenExcept(['email', 'role_name']);
    }

    public function update(UpdateUserRequest $request): bool
    {
        //
    }
}
