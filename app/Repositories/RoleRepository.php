<?php

namespace App\Repositories;

use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Models\{
    Role,
    Permission
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection as IlluminateCollection
};
use Illuminate\Support\{Arr, Collection, Facades\DB};
use Closure;

class RoleRepository extends SearchableRepository implements RoleRepositoryInterface
{
    protected Role $role;

    protected Permission $permission;

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

    public function allNonSystem(array $columns = ['*']): IlluminateCollection
    {
        return $this->role->query()->where('is_system', false)->get($columns);
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

    public function findByModule(string $module, ?Closure $scope = null)
    {
        return $this->role->query()
            ->when($scope, $scope)
            ->orderBy('name')
            ->whereHas('permissions', fn (Builder $query) => $query->whereIn('name', ["view_{$module}", "view_own_{$module}"]))
            ->get();
    }

    public function findByName(string $name): Role
    {
        return $this->role->whereName($name)->firstOrFail();
    }

    public function create(array $attributes): Role
    {
        return DB::transaction(
            fn () => tap($this->role->create($attributes), function (Role $role) use ($attributes) {
                $role->syncPrivileges(Arr::get($attributes, 'properties'));
                $role->companies()->sync(Arr::get($attributes, 'companies') ?? []);
            })
        );
    }

    public function update(string $id, array $attributes): Role
    {
        return DB::transaction(
            fn () => tap($this->find($id), function (Role $role) use ($attributes) {
                $role->update($attributes);
                $role->syncPrivileges(Arr::get($attributes, 'properties'));
                $role->companies()->sync(Arr::get($attributes, 'companies') ?? []);
            }),
            DB_TA
        );
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
