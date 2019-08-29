<?php

namespace App\Contracts\Repositories;

interface UserRepositoryInterface
{
    /**
     * Get all users
     *
     * @return \Illuminate\Database\Eloquent\Collection 
     */
    public function all();

    /**
     * Make a new user
     * @params array
     * @return \App\Models\User
     */
    public function make(Array $array);
}