<?php

namespace App\Models;

use App\Contracts\ActivatableInterface;
use App\Models\Permission;
use App\Traits\{
    Activatable,
    BelongsToUser,
    Search\Searchable,
    Systemable,
    Activity\LogsActivity,
    Auth\Multitenantable,
    HasUsers,
    Uuid
};
use Spatie\Permission\{
    Guard,
    Traits\HasPermissions,
    Traits\RefreshesPermissionCache,
    Exceptions\RoleDoesNotExist,
    Exceptions\GuardDoesNotMatch,
    Exceptions\RoleAlreadyExists,
    Contracts\Role as RoleContract
};
use Illuminate\Database\Eloquent\{
    Model,
    Collection as EloquentCollection,
    SoftDeletes,
    Relations\MorphToMany,
    Relations\BelongsToMany,
};
use Illuminate\Support\Collection;
use Arr;

class Role extends Model implements RoleContract, ActivatableInterface
{
    use Uuid,
        Multitenantable,
        HasPermissions,
        RefreshesPermissionCache,
        BelongsToUser,
        HasUsers,
        Searchable,
        SoftDeletes,
        Activatable,
        Systemable,
        LogsActivity;

    protected $fillable = [
        'name', 'guard_name', 'privileges', 'is_system'
    ];

    protected $hidden = [
        'permissions', 'user', 'deleted_at'
    ];

    protected $casts = [
        'privileges' => 'collection',
        'is_system' => 'boolean'
    ];

    protected static $logAttributes = [
        'name', 'modules_privileges'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.roles'));
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? 'web';

        if (!app()->runningInConsole()) {
            $attributes['user_id'] = $attributes['user_id'] ?? auth()->id();
        }

        if (
            static::where('name', $attributes['name'])
            ->where('guard_name', $attributes['guard_name'])
            ->where(
                fn ($query) => $query->where('user_id', optional($attributes)['user_id'])
                    ->orWhere('is_system', true)
            )
            ->first()
        ) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name']),
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \Spatie\Permission\Contracts\Role|\Spatie\Permission\Models\Role
     *
     * @throws \Spatie\Permission\Exceptions\RoleDoesNotExist
     */
    public static function findByName(string $name, $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (!$role) {
            throw RoleDoesNotExist::named($name);
        }

        return $role;
    }

    public static function findById(int $id, $guardName = null): RoleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $role = static::where('id', $id)->where('guard_name', $guardName)->first();

        if (!$role) {
            throw RoleDoesNotExist::withId($id);
        }

        return $role;
    }

    /**
     * Find or create role by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \Spatie\Permission\Contracts\Role
     */
    public static function findOrCreate(string $name, $guard_name = null): RoleContract
    {
        $guard_name ??= Guard::getDefaultName(static::class);

        $role = static::where(compact('name', 'guard_name'))->first();

        if (!$role) {
            return static::query()->create(compact('name', 'guard_name'));
        }

        return $role;
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     *
     * @throws \Spatie\Permission\Exceptions\GuardDoesNotMatch
     */
    public function hasPermissionTo($permission): bool
    {
        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName($permission, $this->getDefaultGuardName());
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission, $this->getDefaultGuardName());
        }

        if (!$this->getGuardNames()->contains($permission->guard_name)) {
            throw GuardDoesNotMatch::create($permission->guard_name, $this->getGuardNames());
        }

        return $this->permissions->contains('id', $permission->id);
    }

    public function setPrivilegesAttribute($value)
    {
        if (!Arr::accessible($value)) {
            return;
        }

        $modules = array_flip(array_keys(config('role.modules')));

        $value = collect($value)->sortBy(function ($value) use ($modules) {
            return data_get($modules, data_get($value, 'module'));
        })->values()->toJson();

        $this->attributes['privileges'] = $value;
    }

    public function syncPrivileges(?array $properties = null): void
    {
        $permissionsNames = collect($this->privileges)->reduce(function ($carry, $privilege) {
            $permissions = config('role.modules')[$privilege['module']][$privilege['privilege']];
            array_push($carry, ...$permissions);
            return $carry;
        }, []);

        $permissions = Permission::whereIn('name', $permissionsNames)->get();
        $properties = static::getPropertiesPermissions($properties);

        if ($properties->isNotEmpty()) {
            $permissions->push(...$properties);
        }

        $this->syncPermissions($permissions);
    }

    public static function getPropertiesPermissions(?array $properties = null): EloquentCollection
    {
        if (is_null($properties)) {
            return EloquentCollection::make();
        }

        $roleProperties = array_flip(config('role.properties'));

        $permissionsNames = collect($properties)
            ->filter(function ($property) use ($roleProperties) {
                return Arr::has($roleProperties, data_get($property, 'key')) &&
                    data_get($property, 'value', false);
            })
            ->pluck('key')
            ->toArray();

        return Permission::whereIn('name', $permissionsNames)->get();
    }

    public function getPropertiesAttribute(): Collection
    {
        return collect(config('role.properties'))->flip()
            ->transform(function ($value, $key) {
                $value = $this->permissions->pluck('name')->contains($key);
                return $value;
            });
    }

    public function getModulesPrivilegesAttribute()
    {
        return collect()->wrap($this->privileges)->toString('module', 'privilege');
    }

    public static function administrator()
    {
        return static::whereName('Administrator')->system()->firstOrFail();
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }
}
