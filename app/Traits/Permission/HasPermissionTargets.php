<?php

namespace App\Traits\Permission;

use App\Models\Permission;
use Illuminate\Support\Collection;
use Spatie\Permission\WildcardPermission;
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
        $permission = new WildcardPermission($permission);

        return $this->permissions->filter(
            fn (Permission $model) =>
            Str::is('*.*.*', $model->name)
                && static::targetPermissionImplies($permission, (new WildcardPermission($model->name)))
        )
            ->map(fn (Permission $model) => Str::afterLast($model->name, '.'))
            ->filter(fn (string $name) => Str::isUuid($name));
    }

    protected static function targetPermissionImplies(WildcardPermission $permission, WildcardPermission $otherPermission): bool
    {
        $otherParts = $otherPermission->getParts();

        $i = 0;
        foreach ($otherParts as $otherPart) {
            if ($permission->getParts()->count() - 1 < $i) {
                return true;
            }

            if (
                !$permission->getParts()->get($i)->contains(WildcardPermission::WILDCARD_TOKEN)
                && !static::targetPermissionContainsAll($otherPart, $permission->getParts()->get($i))
            ) {
                return false;
            }

            $i++;
        }

        for ($i; $i < $permission->getParts()->count(); $i++) {
            if (!$permission->getParts()->get($i)->contains(WildcardPermission::WILDCARD_TOKEN)) {
                return false;
            }
        }

        return true;
    }

    protected static function targetPermissionContainsAll(Collection $part, Collection $otherPart): bool
    {
        foreach ($otherPart->toArray() as $item) {
            if (!$part->contains($item)) {
                return false;
            }
        }

        return true;
    }
}
