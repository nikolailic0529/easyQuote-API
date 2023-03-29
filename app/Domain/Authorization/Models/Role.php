<?php

namespace App\Domain\Authorization\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Authorization\Services\PermissionHelper;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Systemable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\User\Concerns\HasUsers;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\RefreshesPermissionCache;

/**
 * @property string|null                              $name
 * @property string|null                              $guard_name
 * @property bool|null                                $is_system
 * @property \Illuminate\Database\Eloquent\Collection $permissions
 */
class Role extends Model implements RoleContract, ActivatableInterface, SearchableEntity
{
    use Uuid;
    use Multitenantable;
    use HasPermissions;
    use RefreshesPermissionCache;
    use BelongsToUser;
    use HasUsers;
    use Searchable;
    use SoftDeletes;
    use Activatable;
    use Systemable;
    use LogsActivity;
    use HasFactory;

    protected $fillable = [
        'name', 'guard_name', 'is_system',
    ];

    protected $hidden = [
        'permissions', 'user', 'deleted_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    protected static $logAttributes = [
        'name', 'modules_privileges',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] ??= config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.roles'));
    }

    public static function create(array $attributes = [])
    {
        if (!app()->runningInConsole()) {
            $attributes['user_id'] ??= auth()->id();
        }

        if (
            static::where('name', $attributes['name'])
                ->where('guard_name', $attributes['guard_name'])
                ->where(
                    static fn ($query) => $query->where('user_id', optional($attributes)['user_id'])
                        ->orWhere('is_system', true)
                )
                ->first()
        ) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
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
     * @param string|null $guardName
     *
     * @return \Spatie\Permission\Contracts\Role|\Spatie\Permission\Models\Role
     *
     * @throws \Spatie\Permission\Exceptions\RoleDoesNotExist
     */
    public static function findByName(string $name, $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $role = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (!$role) {
            throw RoleDoesNotExist::named($name);
        }

        return $role;
    }

    public static function findById(int $id, $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $role = static::where('id', $id)->where('guard_name', $guardName)->first();

        if (!$role) {
            throw RoleDoesNotExist::withId($id);
        }

        return $role;
    }

    /**
     * Find or create role by its name (and optionally guardName).
     *
     * @param null $guard_name
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

    public function getPropertiesAttribute(): Collection
    {
        return PermissionHelper::roleProperties($this);
    }

    public function getPrivilegesAttribute(): Collection
    {
        return PermissionHelper::rolePrivileges($this);
    }

    public function getModulesPrivilegesAttribute()
    {
        return Collection::wrap($this->privileges)->toString('module', 'privilege');
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }

    public static function administrator()
    {
        return static::whereName('Administrator')->system()->firstOrFail();
    }
}
