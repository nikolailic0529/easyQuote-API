<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Contracts\Services\PermissionBroker;

/**
 * @method static ?string grantedModuleLevel(string $module, \App\Models\User $user, ?\App\Models\User $provider = null)
 * @method static array providedModules()
 * @method static array providedLevels()
 *
 * @see \App\Contracts\Services\PermissionBroker
 */
class Permission extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PermissionBroker::class;
    }
}
