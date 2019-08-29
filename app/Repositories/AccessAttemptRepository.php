<?php

namespace App\Repositories;

use App\Models\AccessAttempt;
use App\Contracts\Repositories\AccessAttemptRepositoryInterface;

class AccessAttemptRepository implements AccessAttemptRepositoryInterface
{
    public function all()
    {
        return AccessAttempt::all();
    }

    public function create(Array $array)
    {
        return AccessAttempt::create($array);
    }
}