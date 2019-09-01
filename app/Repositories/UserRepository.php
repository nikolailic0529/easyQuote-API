<?php namespace App\Repositories;

use App\Models\User;
use App\Contracts\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function all()
    {
        return User::all();
    }

    public function make(Array $array)
    {
        return User::make($array);
    }
}
