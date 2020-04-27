<?php

namespace App\Traits\Permission;

use App\Models\Permission;
use Illuminate\Support\{
    Str,
    Collection
};

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
