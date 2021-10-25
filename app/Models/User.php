<?php

namespace App\Models;

use App\Contracts\{ActivatableInterface, HasImagesDirectory, SearchableEntity};
use App\Facades\Permission;
use App\Models\{Collaboration\Invitation};
use App\Models\Template\HpeContractTemplate;
use App\Traits\{Activatable,
    Activity\LogsActivity,
    Auth\HasApiTokens,
    Auth\Loginable,
    BelongsToCompany,
    BelongsToCountry,
    BelongsToTimezone,
    Collaboration\HasInvitations,
    Discount\HasDiscounts,
    HasImportableColumns,
    HasQuoteFiles,
    HasQuoteFilesDirectory,
    HasQuotes,
    Margin\HasCountryMargins,
    Notifiable,
    Permission\HasModulePermissions,
    Permission\HasPermissionTargets,
    QuoteTemplate\HasQuoteTemplates,
    QuoteTemplate\HasTemplateFields,
    Search\Searchable,
    User\EnforceableChangePassword,
    User\PerformsActivity,
    Uuid,
    Vendor\HasVendors,
};
use Illuminate\Auth\{Authenticatable, MustVerifyEmail, Passwords\CanResetPassword};
use Illuminate\Contracts\Auth\{Access\Authorizable as AuthorizableContract,
    Authenticatable as AuthenticatableContract,
    CanResetPassword as CanResetPasswordContract
};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsToMany, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null $team_id
 * @property string|null $first_name
 * @property string|null $middle_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $timezone_id
 * @property string|null $password
 * @property string|null $phone
 * @property int|null $failed_attempts
 *
 * @property-read string|null $user_fullname
 * @property-read Team|null $team
 */
class User extends Model implements
    ActivatableInterface,
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    HasImagesDirectory,
    SearchableEntity
{
    use Uuid,
        Authenticatable,
        Authorizable,
        MustVerifyEmail,
        CanResetPassword,
        HasRoles,
        HasPermissionTargets,
        HasModulePermissions,
        HasImportableColumns,
        HasQuotes,
        HasQuoteFiles,
        HasQuoteFilesDirectory,
        HasApiTokens,
        HasInvitations,
        Notifiable,
        BelongsToTimezone,
        BelongsToCountry,
        BelongsToCompany,
        HasCountryMargins,
        HasDiscounts,
        HasVendors,
        HasQuoteTemplates,
        HasTemplateFields,
        Activatable,
        Searchable,
        SoftDeletes,
        LogsActivity,
        Loginable,
        PerformsActivity,
        EnforceableChangePassword,
        HasRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'role_id',
        'team_id',
        'timezone_id',
        'country_id',
        'company_id',
        'hpe_contract_template_id',
        'email',
        'password',
        'phone',
        'default_route',
        'recent_notifications_limit',
        'failed_attempts',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'roles', 'updated_at', 'deleted_at', 'image',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static $logAttributes = [
        'first_name', 'middle_name', 'last_name', 'email', 'phone', 'role.name', 'team.name', 'timezone.text',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function companies(): HasManyDeep
    {
        return $this->hasManyDeep(
            Company::class,
            ['model_has_roles', Role::class, ModelHasRoles::class.' as model_roles'],
            [['model_type', 'model_id'], 'id', 'role_id', 'id'],
            ['id', 'role_id', 'id', ['model_type', 'model_id']],
        );
    }

    public function hpeContractTemplate(): BelongsTo
    {
        return $this->belongsTo(HpeContractTemplate::class)->withDefault();
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeAdministrators(Builder $query): Builder
    {
        return $query->role('Administrator');
    }

    public function scopeNonAdministrators(Builder $query): Builder
    {
        return $query->whereDoesntHave('roles', fn($query) => $query->whereName('Administrator'));
    }

    public function scopeEmail(Builder $query, string $email): Builder
    {
        return $query->whereEmail($email);
    }

    public function interact($model): void
    {
        if ($model instanceof Invitation) {
            $this->assignRole($model->role);
            $model->delete();
        }
    }

    public function getRoleAttribute()
    {
        return $this->roles->first(null, Role::make());
    }

    public function getRoleIdAttribute()
    {
        return $this->role->id;
    }

    public function getRoleNameAttribute()
    {
        return $this->role->name;
    }

    public function setRoleIdAttribute(string $value)
    {
        if (!Role::whereId($value)->exists()) {
            return;
        }

        $this->syncRoles(Role::whereId($value)->firstOrFail());
    }

    public function getPrivilegesAttribute()
    {
        return $this->role->privileges;
    }

    public function getRolePropertiesAttribute()
    {
        return $this->role->properties;
    }

    public function getTimezoneTextAttribute()
    {
        return $this->timezone->text;
    }

    public function imagesDirectory(): string
    {
        return "images/users";
    }

    public function toSearchArray(): array
    {
        return Arr::except($this->toArray(), ['email_verified_at', 'must_change_password', 'timezone_id', 'role_id', 'picture']);
    }

    public function getItemNameAttribute()
    {
        return $this->email;
    }

    public function grantedModuleLevel(string $module)
    {
        return Permission::grantedModuleLevel($module, $this);
    }

    public function withAppends(...$attributes)
    {
        $appends = ['role_id', 'role_name', 'picture', 'privileges', 'role_properties', 'must_change_password'];

        return $this->append(array_merge($appends, $attributes));
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable')->cacheForever();
    }

    public function createImage($file, array $properties = [])
    {
        if (!$file instanceof UploadedFile || !$this instanceof HasImagesDirectory) {
            return $this;
        }

        $modelImagesDir = $this->imagesDirectory();

        $image = ImageManagerStatic::make($file->get());

        if (filled($properties) && isset($properties['width']) && isset($properties['height'])) {
            $image->resize($properties['width'], $properties['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $storageDir = "public/{$modelImagesDir}";

        !Storage::exists($storageDir) && Storage::makeDirectory($storageDir);

        $original = "{$modelImagesDir}/{$file->hashName()}";
        $image->save(Storage::path("public/{$original}"));

        $this->image()->delete();

        $this->image()->create(compact('original'));

        $this->image()->flushQueryCache();

        return $this->load('image');
    }

    public function getPictureAttribute()
    {
        if (!isset($this->image->original_image)) {
            return null;
        }

        return asset('storage/'.$this->image->original_image);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function ledTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_team_leader', 'team_leader_id');
    }

    public function ledTeamUsers(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->ledTeams(), (new Team())->users());
    }
}
