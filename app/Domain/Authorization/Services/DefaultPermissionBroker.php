<?php

namespace App\Domain\Authorization\Services;

use App\Domain\Authorization\Contracts\PermissionBroker as BrokerContract;
use App\Domain\Authorization\Models\{Permission};
use App\Domain\User\Models\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Permission\PermissionRegistrar;

class DefaultPermissionBroker implements BrokerContract
{
    protected PermissionRegistrar $permissionRegistrar;

    public function __construct(protected ConnectionInterface $connection,
                                protected Config $config,
                                PermissionRegistrar $permissionRegistrar)
    {
        $this->permissionRegistrar = $permissionRegistrar->setPermissionClass(Permission::class);
    }

    public function grantedModuleLevel(string $module,
                                       User $user,
                                       ?User $provider = null): ?string
    {
        $provider ??= auth()->user();

        if ($user->hasRole(R_SUPER)) {
            return R_RUD;
        }

        $pattern = implode('.', [$module, '*', 'user', $provider->getKey()]);

        $permission = $user->getPermissionNames()->first(static fn ($permission) => Str::is($pattern, $permission));

        if (!is_null($permission)) {
            return (string) Str::of($permission)->after($module.'.')->before('.user');
        }

        return null;
    }

    #[ArrayShape(['provider_id' => 'mixed', 'module' => 'string', 'level' => 'string', 'granted' => 'array', 'revoked' => 'array'])]
    public function grantModulePermission(array $users,
                                          string $module,
                                          string $level): array
    {
        /** @var \App\Domain\User\Models\User $provider */
        $provider = auth()->user();

        $users = array_filter($users, static fn ($id) => $id !== $provider->getKey());

        $permission = static::getModulePermission($module, $level, $provider);
        $pattern = Str::of(static::modulePermissionPattern($module, $provider));

        /**
         * Revoke target level permissions from non-passed users having target permission level.
         */
        $revokePermissionUsers = User::query()->whereKeyNot($users)
            ->whereHas('permissions', static fn ($q) => $q->whereKey($permission->id))
            ->get();

        $revokePermissionUsers->each(static fn (User $user) => $user->revokePermissionTo($permission));

        /**
         * Grant permission level to the target users.
         */
        $targetUsers = User::query()->whereKey($users)
            ->with([
                'permissions' => static fn ($q) => $q->where('name', 'like', $pattern->replace('*', '%'))
                    ->whereKeyNot($permission->getKey()),
            ])
            ->whereDoesntHave('permissions', static fn ($q) => $q->whereKey($permission->getKey()))
            ->get();

        $targetUsers->each(static function (User $user) use ($permission): void {
            /* Revoke different levels permission if exists. */
            $user->permissions->each(static fn (Permission $permission) => $user->revokePermissionTo($permission));

            /* Grant target level permission */
            $user->givePermissionTo($permission);
        });

        return [
            'provider_id' => $provider->getKey(),
            'module' => $module,
            'level' => $level,
            'granted' => $targetUsers->modelKeys(),
            'revoked' => $revokePermissionUsers->modelKeys(),
        ];
    }

    public function givePermissionToUser(User $user, string $name, ?string $guard = null): void
    {
        $guard ??= $this->getDefaultGuard();

        $permission = $this->permissionRegistrar->getPermissions([
            'name' => $name,
            'guard_name' => $guard,
        ])
            ->first();

        $permission ??= tap(new Permission(), function (Permission $permission) use ($guard, $name): void {
            $permission->name = $name;
            $permission->guard_name = $guard;

            $this->connection->transaction(static fn () => $permission->save());
        });

        $user->givePermissionTo($permission);
    }

    public function providedModules(): array
    {
        return $this->config->get('permission.provided_modules');
    }

    public function providedLevels(): array
    {
        return $this->config->get('permission.provided_levels');
    }

    public function getDefaultGuard(): string
    {
        return 'api';
    }

    protected static function getModulePermission(string $module, string $level, User $provider): Permission
    {
        $name = implode('.', [$module, $level, 'user', $provider->getKey()]);

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Permission::findOrCreate($name, 'api');
    }

    protected static function modulePermissionPattern(string $module, User $provider): string
    {
        return implode('.', [
            $module,
            '*',
            'user',
            $provider->getKey(),
        ]);
    }
}
