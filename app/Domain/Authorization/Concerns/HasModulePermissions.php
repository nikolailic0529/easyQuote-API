<?php

namespace App\Domain\Authorization\Concerns;

use App\Domain\Authorization\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasModulePermissions
{
    public function getModulePermissionProviders(string $permission): Collection
    {
        $permission = ModuleWildcardPermission::make($permission);

        return $this->permissions->filter(
            fn (Permission $model) => $permission->implies($model->name)
        )
            ->map(fn (Permission $model) => Str::afterLast($model->name, ModuleWildcardPermission::PROVIDER_PART.'.'))
            ->filter(fn (string $name) => Str::isUuid($name));
    }
}
