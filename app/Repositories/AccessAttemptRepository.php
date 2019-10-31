<?php namespace App\Repositories;

use App\Contracts\Repositories\AccessAttemptRepositoryInterface;
use App\Models\AccessAttempt;

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

    public function create(array $array)
    {
        return $this->accessAttempt->create($array);
    }
}
