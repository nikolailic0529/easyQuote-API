<?php

namespace App\Domain\Authorization\Events;

use App\Domain\Authorization\Models\Role;
use Illuminate\Database\Eloquent\Model;

final class RoleDeleted
{
    public function __construct(
        public readonly Role $role,
        public readonly ?Model $causer,
    ) {
    }
}
