<?php

namespace App\Domain\Authorization\Facades;

use App\Domain\Authorization\Contracts\PermissionBroker;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ?string grantedModuleLevel(string $module, \App\Domain\User\Models\User $user, ?\App\Domain\User\Models\User $provider = null)
 * @method static array   providedModules()
 * @method static array   providedLevels()
 *
 * @see \App\Domain\Authorization\Contracts\PermissionBroker
 */
class Permission extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PermissionBroker::class;
    }
}
