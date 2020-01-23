<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Contracts\Factories\FailureInterface;

class Failure extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FailureInterface::class;
    }
}
