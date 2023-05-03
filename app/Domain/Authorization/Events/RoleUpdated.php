<?php

namespace App\Domain\Authorization\Events;

use App\Domain\Authorization\Models\Role;
use Illuminate\Database\Eloquent\Model;

final class RoleUpdated
{
    public function __construct(
        public readonly Role $oldRole,
        public readonly Role $role,
        public readonly ?Model $causer,
    ) {
    }
}
