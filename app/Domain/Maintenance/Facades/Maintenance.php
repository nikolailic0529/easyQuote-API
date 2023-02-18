<?php

namespace App\Domain\Maintenance\Facades;

use App\Domain\Maintenance\Contracts\MaintenanceServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static int   status()
 * @method static int   interpretStatusOf(?\App\Domain\Build\Models\Build $build)
 * @method static bool  running()
 * @method static bool  stopped()
 * @method static bool  scheduled()
 * @method static void  putData()
 * @method static array getData()
 *
 * @see \App\Domain\Maintenance\Contracts\MaintenanceServiceInterface
 */
class Maintenance extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MaintenanceServiceInterface::class;
    }
}
