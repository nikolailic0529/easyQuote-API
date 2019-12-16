<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Contracts\Repositories\System\FailureRepositoryInterface;

class Failure extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FailureRepositoryInterface::class;
    }
}
