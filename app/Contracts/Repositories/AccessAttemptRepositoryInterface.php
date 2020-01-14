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
     * @param array $attributes
     * @return \App\Models\AccessAttempt
     */
    public function create(array $attributes): AccessAttempt;

    /**
     * Retrieve recently created attempt or create.
     *
     * @param array $attributes
     * @return \App\Models\AccessAttempt
     */
    public function retrieveOrCreate(array $attributes): AccessAttempt;
}
