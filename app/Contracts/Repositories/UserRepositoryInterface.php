<?php namespace App\Contracts\Repositories;

interface UserRepositoryInterface
{
    /**
     * Make a new user
     * @params array
     * @return \App\Models\User
     */
    public function make(Array $array);
}
