<?php

namespace App\Repositories;

use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Models\{
    Role,
    Permission
};
use App\Http\Requests\Role\{
    StoreRoleRequest,
    UpdateRoleRequest
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection as IlluminateCollection
};
use Illuminate\Support\Collection;
use Arr;

class RoleRepository extends SearchableRepository implements RoleRepositoryInterface
{
    protected $role;

    public function __construct(Role $role, Permission $permission)
    {
        $this->role = $role;
        $this->permission = $permission;
    }

    public function data(): Collection
    {
        $privileges = config('role.privileges');
        $modules = collect(config('role.modules'))->keys();
        $properties = config('role.properties');

        return collect(compact('privileges', 'modules', 'properties'));
    }

    public function allActivated(array $columns = ['*']): IlluminateCollection
    {
        return $this->userQuery()->activated()->get($columns);
    }

    public function userQuery(): Builder
    {
        return $this->role->query();
    }

    public function find(string $id): Role
    {
        return $this->userQuery()
            ->whereId($id)->firstOrFail()->append('properties');
    }

    public function findByName(string $name): Role
    {
        return $this->role->whereName($name)->firstOrFail();
    }

    public function create($request): Role
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
            data_set($request, 'user_id', auth()->id());
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return tap($this->role->create($request), function ($role) use ($request) {
            $role->syncPrivileges(data_get($request, 'properties'));
            $role->append('properties');
        });
    }

    public function update(UpdateRoleRequest $request, string $id): Role
    {
        $role = $this->find($id);

        return tap($role, function ($role) use ($request) {
            $role->update($request->validated());
            $role->syncPrivileges($request->input('properties'));
            $role->append('properties');
        });
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
            \App\Http\Query\OrderByName::class,
            \App\Http\Query\OrderByCreatedAt::class
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
        return $this->role;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'created_at'
        ];
    }

    protected function searchableScope($query)
    {
        return $query;
    }
}
