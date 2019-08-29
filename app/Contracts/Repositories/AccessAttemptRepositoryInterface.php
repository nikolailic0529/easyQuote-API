<?php

namespace App\Contracts\Repositories;

interface AccessAttemptRepositoryInterface
{
    /**
     * Get all access attempts
     *
     * @return \Illuminate\Database\Eloquent\Collection 
     */
    public function all();

    /**
     * Create a new access attempt
     * @params Array $array
     * @return \App\Models\AccessAttempt
     */
    public function create(Array $array);
}