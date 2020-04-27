<?php

namespace App\Listeners;

use App\Contracts\Repositories\UserRepositoryInterface as Users;
use App\Events\Permission\GrantedModulePermission;
use App\Notifications\{
    GrantedModulePermission as GrantedNotification,
    RevokedModulePermission as RevokedNotification,
};
use App\Models\User;
use Illuminate\Support\{
    Arr,
    Str
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ModulePermissionListener
{
    protected Users $users;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Users $users)
    {
        $this->users = $users;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(GrantedModulePermission $event)
    {
        $result = $event->result;

        $provider = $this->users->find(Arr::get($result, 'provider_id'));
        $granted = $this->users->findMany(Arr::get($result, 'granted'));
        $revoked = $this->users->findMany(Arr::get($result, 'revoked'));

        $module = Arr::get($result, 'module');
        $level = Arr::get($result, 'level');

        $prettifiedLevel = static::prettifyAccessLevel($level);

        $grantedMessage = sprintf('User %s has granted you %s access to his own %s', $provider->email, $prettifiedLevel, $module);
        $revokedMessage = sprintf('User %s has revoked from you %s access to his own %s', $provider->email, $prettifiedLevel, $module);

        $granted->each(function (User $user) use ($provider, $module, $prettifiedLevel, $grantedMessage) {
            $user->notify(new GrantedNotification($provider, $module, $prettifiedLevel));

            notification()->for($user)->message($grantedMessage)->priority(1)->store();
        });

        $revoked->each(function (User $user) use ($provider, $module, $prettifiedLevel, $revokedMessage) {
            $user->notify(new RevokedNotification($provider, $module, $prettifiedLevel));

            notification()->for($user)->message($revokedMessage)->priority(1)->store();
        });
    }

    protected static function prettifyAccessLevel(string $level): string
    {
        return (string) Str::of($level)->explode(',')->map(fn ($l) => Str::ucfirst($l))->implode(', ', ' and ');
    }
}
