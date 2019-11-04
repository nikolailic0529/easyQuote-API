<?php namespace App\Repositories;

use App\Contracts\Repositories \ {
    UserRepositoryInterface,
    CountryRepositoryInterface as CountryRepository,
    TimezoneRepositoryInterface as TimezoneRepository
};
use App\Http\Requests \ {
    Collaboration\InviteUserRequest,
    Collaboration\UpdateUserRequest
};
use App\Models \ {
    User,
    Role,
    Collaboration\Invitation
};
use Illuminate\Database\Eloquent \ {
    Model,
    Builder,
    Collection
};
use Illuminate\Support\Collection as SupportCollection;
use Hash;

class UserRepository extends SearchableRepository implements UserRepositoryInterface
{
    protected $user;

    protected $role;

    protected $invitation;

    protected $country;

    protected $timezone;

    public function __construct(
        User $user,
        Role $role,
        Invitation $invitation,
        CountryRepository $country,
        TimezoneRepository $timezone
    ) {
        $this->user = $user;
        $this->role = $role;
        $this->invitation = $invitation;
        $this->country = $country;
        $this->timezone = $timezone;
    }

    public function userQuery(): Builder
    {
        return $this->user->query()->userCollaborationExcept();
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
        return $this->invitation->whereInvitationToken($token)->firstOrFail()->makeHiddenExcept(['email', 'role_name']);
    }

    public function update(UpdateUserRequest $request, string $id): bool
    {
        return $this->find($id)->update($request->validated());
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

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\User\OrderByEmail::class,
            \App\Http\Query\User\OrderByFullname::class,
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
        return $query->userCollaborationExcept();
    }
}
