<?php

namespace App\Domain\Authorization\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Authorization\DataTransferObjects\AccessData;
use App\Domain\Authorization\DataTransferObjects\CreateRoleData;
use App\Domain\Authorization\DataTransferObjects\SetAccessData;
use App\Domain\Authorization\DataTransferObjects\UpdateRoleData;
use App\Domain\Authorization\Events\RoleCreated;
use App\Domain\Authorization\Events\RoleDeleted;
use App\Domain\Authorization\Events\RoleUpdated;
use App\Domain\Authorization\Models\Role;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

final class RoleEntityService implements CauserAware
{
    private ?Model $causer = null;

    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly Dispatcher $eventDispatcher,
    ) {
    }

    public function createRole(CreateRoleData $data): Role
    {
        return tap(new Role(), function (Role $role) use ($data): void {
            $role->name = $data->name;

            if ($data->accessData instanceof SetAccessData) {
                $role->access = [$role->access, ...$data->accessData->toArray()];
            } else {
                $role->access = AccessData::empty();
            }

            $permissionModel = $this->getPermissionModel();

            $permissionIDs = $permissionModel->newQuery()
                ->whereIn('name', $data->permissions)
                ->pluck($permissionModel->getKeyName());

            $this->conResolver->connection()->transaction(static function () use ($role, $permissionIDs): void {
                $role->save();
                $role->permissions()->sync($permissionIDs);
            });

            $role->forgetCachedPermissions();

            $this->eventDispatcher->dispatch(
                new RoleCreated(role: $role, causer: $this->causer)
            );
        });
    }

    public function updateRole(Role $role, UpdateRoleData $data): Role
    {
        return tap($role, function (Role $role) use ($data): void {
            $oldRole = $this->cloneRole($role);

            $role->name = $data->name;
            if ($data->accessData instanceof SetAccessData) {
                $role->access = [$role->access, ...$data->accessData->toArray()];
            }

            $permissionModel = $this->getPermissionModel();

            $permissionIDs = $permissionModel->newQuery()
                ->whereIn('name', $data->permissions)
                ->pluck($permissionModel->getKeyName());

            $this->conResolver->connection()->transaction(static function () use ($role, $permissionIDs): void {
                $role->save();
                $role->permissions()->sync($permissionIDs);
            });

            $role->forgetCachedPermissions();

            $this->eventDispatcher->dispatch(
                new RoleUpdated(
                    oldRole: $oldRole,
                    role: $role,
                    causer: $this->causer,
                )
            );
        });
    }

    public function deleteRole(Role $role): void
    {
        $role->delete();

        $role->forgetCachedPermissions();

        $this->eventDispatcher->dispatch(
            new RoleDeleted(role: $role, causer: $this->causer)
        );
    }

    public function markRoleAsActive(Role $role)
    {
        return tap($role, function (Role $role): void {
            $oldRole = $this->cloneRole($role);

            $role->activated_at = now();

            $role->save();

            $this->eventDispatcher->dispatch(
                new RoleUpdated(
                    oldRole: $oldRole,
                    role: $role,
                    causer: $this->causer,
                )
            );
        });
    }

    public function markRoleAsInactive(Role $role)
    {
        return tap($role, function (Role $role): void {
            $oldRole = $this->cloneRole($role);

            $role->activated_at = null;

            $role->save();

            $this->eventDispatcher->dispatch(
                new RoleUpdated(
                    oldRole: $oldRole,
                    role: $role,
                    causer: $this->causer,
                )
            );
        });
    }

    private function cloneRole(Role $role): Role
    {
        return (new Role())->setRawAttributes($role->getRawOriginal())->load('permissions');
    }

    private function getPermissionModel(): Model
    {
        return (new Role())->permissions()->getModel();
    }

    public function setCauser(?Model $causer): static
    {
        $this->causer = $causer;

        return $this;
    }
}
