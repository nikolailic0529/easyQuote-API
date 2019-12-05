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
    Builder
};
use Illuminate\Support\Collection;

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

        return collect(compact('privileges', 'modules'));
    }

    public function userQuery(): Builder
    {
        return $this->role->query();
    }

    public function find(string $id): Role
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StoreRoleRequest $request): Role
    {
        return $this->role->create($request->validated())->syncPrivileges();
    }

    public function update(UpdateRoleRequest $request, string $id): Role
    {
        $role = $this->find($id);
        $role->update($request->validated());
        $role->syncPrivileges();

        return $role;
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

    protected function searchableScope(Builder $query)
    {
        return $query;
    }
}
