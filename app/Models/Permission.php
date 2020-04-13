<?php

namespace App\Models;

use App\Traits\Uuid;
use Spatie\Permission\{
    Guard,
    PermissionRegistrar,
    Traits\HasRoles,
    Traits\RefreshesPermissionCache,
    Exceptions\PermissionAlreadyExists,
    Exceptions\PermissionDoesNotExist,
    Contracts\Permission as PermissionContract,
};
use Illuminate\Database\Eloquent\{
    Model,
    Relations\MorphToMany,
    Relations\BelongsToMany
};
use Illuminate\Support\Collection;
use Arr;

class Permission extends Model implements PermissionContract
{
    use Uuid, HasRoles, RefreshesPermissionCache;

    protected $guarded = ['id'];

    protected $hidden = ['pivot'];

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.permissions'));
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

        $permission = static::getPermissions(Arr::only($attributes, ['name', 'guard_name']))->first();

        if ($permission) {
            throw PermissionAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A permission can be applied to roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions'),
            'permission_id',
            'role_id'
        );
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name']),
            'model',
            config('permission.table_names.model_has_permissions'),
            'permission_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * Find a permission by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @throws \Spatie\Permission\Exceptions\PermissionDoesNotExist
     *
     * @return \Spatie\Permission\Contracts\Permission
     */
    public static function findByName(string $name, $guard_name = null): PermissionContract
    {
        $guard_name ??= Guard::getDefaultName(static::class);

        $permission = static::getPermissions(compact('name', 'guard_name'))->first();

        if (!$permission) {
            throw PermissionDoesNotExist::create($name, $guard_name);
        }

        return $permission;
    }

    /**
     * Find a permission by its id (and optionally guardName).
     *
     * @param int $id
     * @param string|null $guardName
     *
     * @throws \Spatie\Permission\Exceptions\PermissionDoesNotExist
     *
     * @return \Spatie\Permission\Contracts\Permission
     */
    public static function findById(int $id, $guard_name = null): PermissionContract
    {
        $guard_name ??= Guard::getDefaultName(static::class);

        $permission = static::getPermissions(compact('id', 'guard_name'))->first();

        if (!$permission) {
            throw PermissionDoesNotExist::withId($id, $guard_name);
        }

        return $permission;
    }

    /**
     * Find or create permission by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \Spatie\Permission\Contracts\Permission
     */
    public static function findOrCreate(string $name, $guard_name = null): PermissionContract
    {
        $guard_name ??= Guard::getDefaultName(static::class);
        $permission = static::getPermissions(compact('name', 'guard_name'))->first();

        if (!$permission) {
            return static::query()->create(compact('name', 'guard_name'));
        }

        return $permission;
    }

    /**
     * Get the current cached permissions.
     */
    protected static function getPermissions(array $params = []): Collection
    {
        return app(PermissionRegistrar::class)
            ->setPermissionClass(static::class)
            ->getPermissions($params);
    }
}
