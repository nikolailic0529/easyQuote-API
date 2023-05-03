<?php

namespace App\Domain\Authorization\Providers;

use App\Domain\Authorization\Events\GrantedModulePermission;
use App\Domain\Authorization\Listeners\ModulePermissionListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class PermissionEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        GrantedModulePermission::class => [
            ModulePermissionListener::class,
        ],
    ];
}
