<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Contracts\Repositories\System\SystemSettingRepositoryInterface;

class Setting extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SystemSettingRepositoryInterface::class;
    }
}
