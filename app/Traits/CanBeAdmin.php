<?php

namespace App\Traits;

use App\Models\Role;

trait CanBeAdmin
{
    public function setAsAdmin()
    {
        $adminRole = Role::admin()->id;
        return $this->role()->associate($adminRole);
    }
}