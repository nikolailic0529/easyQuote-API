<?php

namespace App\Services;

use App\Models\{
    User,
    Permission
};
use Illuminate\Support\Str;
use App\Contracts\Services\PermissionBroker as BrokerContract;

class PermissionBroker implements BrokerContract
{
    public function __construct()
    {
    }

    public function grantedModuleLevel(string $module, User $user, ?User $provider = null)
    {
        $provider ??= auth()->user();

        if ($user->hasRole(R_SUPER)) {
            return R_RUD;
        }

        $pattern = implode('.', [$module, '*', 'user', $provider->id]);

        $permission = $user->getPermissionNames()->first(fn ($permission) => Str::is($pattern, $permission));

        if (is_null($permission)) {
            return $permission;
        }

        return (string) Str::of($permission)->after($module . '.')->before('.user');
    }

    public function grantModulePermission(array $users, string $module, string $level)
    {
        $provider ??= auth()->user();

        $users = array_filter($users, fn ($id) => $id !== $provider->id);

        $permission = static::getModulePermission($module, $level, $provider);
        $pattern = Str::of(static::modulePermissionPattern($module, $provider));

        /**
         * Revoke target level permissions from non-passed users having target permission level.
         */
        $revokePermissionUsers = User::whereKeyNot($users)
            ->whereHas('permissions', fn ($q) => $q->whereKey($permission->id))
            ->get();

        $revokePermissionUsers->each(fn (User $user) => $user->revokePermissionTo($permission));

        /**
         * Grant permission level to the target users.
         */
        $targetUsers = User::whereKey($users)
            ->with([
                'permissions' => fn ($q) =>
                $q->where('name', 'like', $pattern->replace('*', '%'))
                    ->whereKeyNot($permission->id)
            ])
            ->whereDoesntHave('permissions', fn ($q) => $q->whereKey($permission->id))
            ->get();

        $targetUsers->each(function (User $user) use ($permission) {
            /** Revoke different levels permission if exist. */
            $user->permissions->each(fn (Permission $permission) => $user->revokePermissionTo($permission));

            /** Grant target level permission */
            $user->givePermissionTo($permission);
        });

        return [
            'provider_id' => $provider->id,
            'module' => $module,
            'level' => $level,
            'granted' => $targetUsers->pluck('id')->toArray(),
            'revoked' => $revokePermissionUsers->pluck('id')->toArray()
        ];
    }

    public function providedModules(): array
    {
        return config('permission.provided_modules');
    }

    public function providedLevels(): array
    {
        return config('permission.provided_levels');
    }

    protected static function getModulePermission(string $module, string $level, User $provider): Permission
    {
        $name = implode('.', [$module, $level, 'user', $provider->id]);

        return Permission::findOrCreate($name, 'web');
    }

    protected static function modulePermissionPattern(string $module, User $provider)
    {
        return implode('.', [$module, '*', 'user', $provider->id]);
    }
}
