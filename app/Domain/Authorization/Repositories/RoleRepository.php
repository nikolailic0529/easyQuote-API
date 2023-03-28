<?php

namespace App\Domain\Authorization\Repositories;

use App\Domain\Authorization\Contracts\RoleRepositoryInterface;
use App\Domain\Authorization\Models\{Permission};
use App\Domain\Authorization\Models\Role;
use App\Domain\Shared\Eloquent\Repository\SearchableRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as IlluminateCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RoleRepository extends SearchableRepository implements RoleRepositoryInterface
{
    protected Role $role;

    protected Permission $permission;

    public function __construct(Role $role, Permission $permission)
    {
        $this->role = $role;
        $this->permission = $permission;
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
        return $this->role->query()->whereKey($id)->firstOrFail();
    }

    public function findByModule(string $module, ?\Closure $scope = null)
    {
        return $this->role->query()
            ->when($scope, $scope)
            ->orderBy('name')
            ->whereHas('permissions',
                static fn (Builder $query) => $query->whereIn('name', ["view_{$module}", "view_own_{$module}"]))
            ->get();
    }

    public function findByName(string $name): Role
    {
        return $this->role->whereName($name)->firstOrFail();
    }

    public function create(array $attributes): Role
    {
        return tap(new Role($attributes), static function (Role $role) use ($attributes): void {
            $permissions = $attributes['permissions'] ?? [];

            DB::transaction(static function () use ($permissions, $role): void {
                $role->save();

                if ($permissions) {
                    $role->permissions()->sync($permissions);
                }
            });

            $role->forgetCachedPermissions();
        });
    }

    public function update(string $id, array $attributes): Role
    {
        $role = $this->find($id);

        return DB::transaction(
            static fn () => tap($role->fill($attributes), static function (Role $role) use ($attributes): void {
                $role->save();

                $role->permissions()->sync(Arr::get($attributes, 'permissions') ?? []);

                $role->forgetCachedPermissions();
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
            \App\Domain\Authorization\Queries\Filters\OrderByName::class,
            \App\Domain\Authorization\Queries\Filters\OrderByCreatedAt::class,
            \App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy::class,
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated(),
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->role;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'created_at',
        ];
    }

    protected function searchableScope($query)
    {
        return $query;
    }
}
