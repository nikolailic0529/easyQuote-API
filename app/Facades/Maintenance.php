<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Contracts\Services\MaintenanceServiceInterface;

/**
 * @method static int status()
 * @method static int interpretStatusOf(?\App\Models\System\Build $build)
 * @method static bool running()
 * @method static bool stopped()
 * @method static bool scheduled()
 * @method static void putData()
 * @method static array getData()
 *
 * @see \App\Contracts\Services\MaintenanceServiceInterface
 */
class Maintenance extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MaintenanceServiceInterface::class;
    }
}
