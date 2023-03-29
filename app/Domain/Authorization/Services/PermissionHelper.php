<?php

namespace App\Domain\Authorization\Services;

use App\Domain\Authorization\Models\Permission;
use App\Domain\Authorization\Models\Role;
use Illuminate\Support\Collection;

class PermissionHelper
{
    protected static ?\WeakMap $rolePermissionsCache = null;

    /**
     * Retrieve Module Privilege Permissions.
     *
     * @param string $module
     * @param string $privilege
     */
    public static function modulePermissions($module, $privilege): array
    {
        $module = config('role.modules')[$module] ?? [];

        return $module[$privilege] ?? [];
    }

    /**
     * Retrieve Sub-Module Privilege Permissions.
     *
     * @param string $module
     * @param string $privilege
     */
    public static function subModulePermissions($module, $subModule, $privilege): array
    {
        $subModule = (config('role.submodules')[$module] ?? [])[$subModule] ?? [];

        return $subModule[$privilege] ?? [];
    }

    /**
     * Retrieve Permissions Model Keys.
     *
     * @param Collection|string[]|string $name
     */
    public static function permissionKey($name): array
    {
        $names = Collection::wrap($name)->filter();

        return Permission::query()->whereIn('name', $names)->pluck('id')->toArray();
    }

    public static function rolePrivileges(Role $role): Collection
    {
        return Collection::wrap(config('role.modules'))
            ->map(static function (array $modulePrivileges, string $moduleName) use ($role): array {
                $privilege = static::resolveMostSignificantPrivilege($role, $modulePrivileges);
                $subModules = static::resolveSubmodules($role, $moduleName);

                return [
                    'module' => $moduleName,
                    'privilege' => $privilege,
                    'submodules' => $subModules->sortBy('submodule')->values(),
                ];
            })
            ->whereNotNull('privilege')
            ->sortBy('module', SORT_NATURAL)
            ->values();
    }

    public static function roleProperties(Role $role): Collection
    {
        $permissionMap = static::getPermissionsMap($role);

        return Collection::wrap(config('role.properties'))
            ->mapWithKeys(static fn ($value) => [$value => key_exists($value, $permissionMap)]);
    }

    protected static function resolveSubmodules(Role $role, string $module): Collection
    {
        return Collection::wrap(config('role.submodules')[$module] ?? [])
            ->map(static fn (array $privileges, string $submodule): array => [
                'submodule' => $submodule,
                'privilege' => static::resolveMostSignificantPrivilege($role, $privileges),
            ])
            ->whereNotNull('privilege')
            ->values();
    }

    protected static function resolveMostSignificantPrivilege(Role $role, array $privileges)
    {
        $mostSignificantPrivilege = null;

        if ($role->name === R_SUPER) {
            return last(array_keys($privileges));
        }

        $permissionMap = static::getPermissionsMap($role);

        foreach ($privileges as $privilege => $permissions) {
            foreach ($permissions as $permission) {
                if (!key_exists($permission, $permissionMap)) {
                    return $mostSignificantPrivilege;
                }
            }

            $mostSignificantPrivilege = $privilege;
        }

        return $mostSignificantPrivilege;
    }

    protected static function getPermissionsMap(Role $role): array
    {
        static::$rolePermissionsCache ??= new \WeakMap();

        return static::$rolePermissionsCache[$role] ??= $role->permissions->pluck('id', 'name')->all();
    }
}
