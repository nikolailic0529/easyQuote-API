<?php

namespace App\Domain\Authorization\Concerns;

use App\Domain\Authorization\Models\Permission;
use Illuminate\Support\Collection;

trait HasPermissionTargets
{
    /**
     * Retrieve targets from the given permission wildcard.
     */
    public function getPermissionTargets(string $permission): Collection
    {
        $permission = TargetWildcardPermission::make($permission);

        return $this->permissions->filter(
            fn (Permission $model) => $permission->implies($model->name)
        )
            ->map(fn (Permission $model) => \Str::afterLast($model->name, '.'))
            ->filter(fn (string $name) => \Str::isUuid($name));
    }
}
