<?php namespace App\Repositories;

use App\Models\AccessAttempt;
use App\Contracts\Repositories\AccessAttemptRepositoryInterface;

class AccessAttemptRepository implements AccessAttemptRepositoryInterface
{
    protected $accessAttempt;

    public function __construct(AccessAttempt $accessAttempt)
    {
        $this->accessAttempt = $accessAttempt;
    }

    public function all()
    {
        return $this->accessAttempt->all();
    }

    public function create(Array $array)
    {
        return $this->accessAttempt->create($array);
    }
}
