<?php

namespace App\Domain\Settings\Facades;

use App\Domain\Settings\Contracts\SystemSettingRepositoryInterface;
use Illuminate\Support\Facades\Facade;

class Setting extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SystemSettingRepositoryInterface::class;
    }
}
