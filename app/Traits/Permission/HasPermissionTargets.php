<?php

namespace App\Traits\Permission;

use App\Models\Permission;
use Illuminate\Support\Collection;
use Str;

trait HasPermissionTargets
{
    /**
     * Retrieve targets from the given permission wildcard.
     *
     * @param string $permission
     * @return Collection
     */
    public function getPermissionTargets(string $permission): Collection
    {
        $permission = TargetWildcardPermission::make($permission);

        return $this->permissions->filter(
            fn (Permission $model) => $permission->implies($model->name)
        )
            ->map(fn (Permission $model) => Str::afterLast($model->name, '.'))
            ->filter(fn (string $name) => Str::isUuid($name));
    }
}
