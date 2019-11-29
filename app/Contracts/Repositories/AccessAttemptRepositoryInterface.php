<?php

namespace App\Contracts\Repositories;

use App\Models\AccessAttempt;

interface AccessAttemptRepositoryInterface
{
    /**
     * Get all access attempts.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Create a new access attempt.
     *
     * @param array $array
     * @return AccessAttempt
     */
    public function create(array $array): AccessAttempt;
}
