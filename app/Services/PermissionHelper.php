<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class PermissionHelper
{
    /**
     * Retrieve Module Privilege Permissions.
     *
     * @param  string $module
     * @param  string $privilege
     * @return array
     */
    public static function modulePermissions($module, $privilege): array
    {
        $module = config('role.modules')[$module] ?? [];

        return $module[$privilege] ?? [];
    }

    /**
     * Retrieve Sub-Module Privilege Permissions.
     *
     * @param  string $module
     * @param  string $privilege
     * @return array
     */
    public static function subModulePermissions($module, $subModule, $privilege): array
    {
        $subModule = (config('role.submodules')[$module] ?? [])[$subModule] ?? [];

        return $subModule[$privilege] ?? [];
    }

    /**
     * Retrieve Permissions Model Keys.
     *
     * @param  Collection|string[]|string $name
     * @return array
     */
    public static function permissionKey($name): array
    {
        $names = Collection::wrap($name)->filter();

        return Permission::query()->whereIn('name', $names)->pluck('id')->toArray();
    }

    public static function roleCachedPermissions(Role $role): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection */
        $permissions = app(PermissionRegistrar::class)->getPermissions();

        return $permissions->reduce(function (array $rolePermissions, Permission $permission) use ($role) {
            if ($permission->roles->contains('id', $role->getKey())) {
                $rolePermissions[$permission->getKey()] = $permission->name;
            }

            return $rolePermissions;
        }, []);
    }

    public static function rolePrivileges(Role $role): Collection
    {
        return Collection::wrap(config('role.modules'))
            ->map(function ($module, $moduleName) use ($role) {
                $privilege = static::findRelevantRolePrivilege($role, $module);

                $subModules = static::retrieveRoleModuleSubmodules($role, $moduleName);

                return [
                    'module' => $moduleName,
                    'privilege' => $privilege,
                    'submodules' => $subModules->sortBy('submodule')->values()
                ];
            })
            ->whereNotNull('privilege')
            ->sortBy('module', SORT_NATURAL)
            ->values();
    }

    public static function roleProperties(Role $role): Collection
    {
        return Collection::wrap(config('role.properties'))
            ->mapWithKeys(fn ($value) => [$value => $role->hasCachedPermissionTo($value)]);
    }

    protected static function retrieveRoleModuleSubmodules(Role $role, string $module)
    {
        return Collection::wrap(config('role.submodules')[$module] ?? [])
            ->map(fn ($privileges, $submodule) => [
                'submodule' => $submodule,
                'privilege' => static::findRelevantRolePrivilege($role, $privileges)
            ])
            ->whereNotNull('privilege')
            ->values();
    }

    protected static function findRelevantRolePrivilege(Role $role, array $privileges)
    {
        $privilege = null;

        if ($role->name === R_SUPER) {
            return last(array_keys($privileges));
        }

        foreach ($privileges as $key => $permissions) {
            if ($role->hasCachedPermissionTo(...$permissions)) {
                $privilege = $key;
            } else {
                break;
            }
        }

        return $privilege;
    }
}
