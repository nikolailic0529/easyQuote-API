<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property-read Collection<int, User> $assignedToUsers
 */
interface AssignableToUsers
{
    public function assignedToUsers(): MorphToMany;
}