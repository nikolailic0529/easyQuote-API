<?php

namespace App\Traits\Permission;

use Spatie\Permission\WildcardPermission;

class TargetWildcardPermission extends WildcardPermission
{
    public static function make(string $permission)
    {
        return new static($permission);
    }

    /**
     * @param string|WildcardPermission $permission
     *
     * @return bool
     */
    public function implies($permission): bool
    {
        if (is_string($permission)) {
            $permission = new self($permission);
        }

        $otherParts = $permission->getParts();

        if ($otherParts->count() !== 3) {
            return false;
        }

        $i = 0;
        foreach ($otherParts as $otherPart) {
            if ($this->getParts()->count() - 1 < $i) {
                return true;
            }

            if (
                !$this->getParts()->get($i)->contains(static::WILDCARD_TOKEN)
                && !$this->containsAll($otherPart, $permission->getParts()->get($i))
            ) {
                return false;
            }

            $i++;
        }

        for ($i; $i < $this->getParts()->count(); $i++) {
            if (!$this->getParts()->get($i)->contains(static::WILDCARD_TOKEN)) {
                return false;
            }
        }

        return true;
    }
}
