<?php namespace App\Repositories;

use App\Models\User;
use App\Contracts\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function make(Array $array)
    {
        return $this->user->make($array);
    }
}
