<?php

namespace App\Domain\Authorization\Services;

use App\Domain\Authorization\Contracts\ModuleRepository;
use App\Domain\Authorization\Contracts\RolePropertyRepository;
use App\Domain\Authorization\Models\Permission;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Repositories\Models\Privilege;
use App\Domain\Authorization\Repositories\Models\SubModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class RolePresenter
{
    private \WeakMap $permissionCache;

    public function __construct(
        private readonly ModuleRepository $moduleRepository,
        private readonly RolePropertyRepository $propertyRepository,
    ) {
        $this->permissionCache = new \WeakMap();
    }

    /**
     * @return list<array{module: string, privilege: string, submodules: list<array{submodule: string, privilege: string}>>
     */
    public function presentModules(Role $role): array
    {
        $privileges = Collection::empty();

        foreach ($this->moduleRepository as $module) {
            $mostSignificantPrivilege = $this->resolveMostSignificantPrivilege($role, $module->privileges);

            if (!$mostSignificantPrivilege) {
                continue;
            }

            $privileges[] = [
                'module' => $module->name,
                'privilege' => $mostSignificantPrivilege->level,
                'submodules' => $this->presentSubmodules($role, $module->subModules),
            ];
        }

        return $privileges
            ->sortBy('module', SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    public function presentProperties(Role $role): array
    {
        $permissionsMap = $this->getPermissionsMap($role);

        $properties = [];

        foreach ($this->propertyRepository as $prop) {
            $properties[$prop] = key_exists($prop, $permissionsMap);
        }

        return $properties;
    }

    /**
     * @param list<SubModule> $subModules
     *
     * @return list<array{submodule: string, privilege: string}>
     */
    private function presentSubmodules(Role $role, array $subModules): array
    {
        return collect($subModules)
            ->lazy()
            ->map(function (SubModule $subModule) use ($role): array {
                return [
                    'submodule' => $subModule->name,
                    'privilege' => $this->resolveMostSignificantPrivilege($role, $subModule->privileges)?->level,
                ];
            })
            ->whereNotNull('privilege')
            ->sortBy('submodule', SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * @param list<Privilege> $privileges
     */
    private function resolveMostSignificantPrivilege(Role $role, array $privileges): ?Privilege
    {
        $mostSignificantPrivilege = null;

        if (R_SUPER === $role->name) {
            return last($privileges);
        }

        $permissionMap = $this->getPermissionsMap($role);

        foreach ($privileges as $privilege) {
            foreach ($privilege->permissions as $permission) {
                if (!key_exists($permission, $permissionMap)) {
                    continue 2;
                }
            }

            $mostSignificantPrivilege = $privilege;
        }

        return $mostSignificantPrivilege;
    }

    private function getPermissionsMap(Role $role): array
    {
        return $this->permissionCache[$role] ??= $role->permissions->pluck($this->getPermissionsModel()->getKeyName(), 'name')->all();
    }

    private function getPermissionsModel(): Model
    {
        return new Permission();
    }
}
