<?php

namespace App\Domain\Authorization\Listeners;

use App\Domain\Authorization\Events\GrantedModulePermission;
use App\Domain\Authorization\Notifications\GrantedModulePermissionNotification;
use App\Domain\Authorization\Notifications\RevokedModulePermissionNotification;
use App\Domain\User\Contracts\UserRepositoryInterface as Users;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ModulePermissionListener
{
    public function __construct(
        protected readonly Users $users
    ) {
    }

    public function handle(GrantedModulePermission $event): void
    {
        $result = $event->result;

        $provider = $this->users->find(Arr::get($result, 'provider_id'));
        $granted = $this->users->findMany(Arr::get($result, 'granted'));
        $revoked = $this->users->findMany(Arr::get($result, 'revoked'));

        $module = Arr::get($result, 'module');
        $level = Arr::get($result, 'level');

        $prettifiedLevel = static::prettifyAccessLevel($level);

        Notification::send($granted, new GrantedModulePermissionNotification($provider, $module, $prettifiedLevel));
        Notification::send($revoked, new RevokedModulePermissionNotification($provider, $module, $prettifiedLevel));
    }

    protected static function prettifyAccessLevel(string $level): string
    {
        return (string) Str::of($level)->explode(',')->map(fn ($l) => Str::ucfirst($l))->implode(', ', ' and ');
    }
}
