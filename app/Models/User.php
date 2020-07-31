<?php

namespace App\Models;

use App\Contracts\{
    ActivatableInterface,
    WithImage
};
use App\Facades\Permission;
use App\Models\{
    Role,
    Collaboration\Invitation
};
use App\Models\QuoteTemplate\HpeContractTemplate;
use App\Traits\{
    Activatable,
    HasCountry,
    BelongsToTimezone,
    HasQuoteFilesDirectory,
    HasQuoteFiles,
    HasQuotes,
    HasImportableColumns,
    Margin\HasCountryMargins,
    Discount\HasDiscounts,
    Vendor\HasVendors,
    Company\HasCompanies,
    QuoteTemplate\HasQuoteTemplates,
    QuoteTemplate\HasTemplateFields,
    Collaboration\HasInvitations,
    Search\Searchable,
    Image\HasImage,
    Image\HasPictureAttribute,
    Auth\Loginable,
    Auth\HasApiTokens,
    User\EnforceableChangePassword,
    User\PerformsActivity,
    Activity\LogsActivity,
    BelongsToCompany,
    BelongsToCountry,
    Permission\HasPermissionTargets,
    Permission\HasModulePermissions,
    Notifiable,
    Uuid,
};
use App\Traits\QuoteTemplate\BelongsToQuoteTemplate;
use Illuminate\Database\Eloquent\{
    Builder,
    Collection,
    Model,
    SoftDeletes,
};
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\{
    Authenticatable as AuthenticatableContract,
    Access\Authorizable as AuthorizableContract,
    CanResetPassword as CanResetPasswordContract
};
use Illuminate\Auth\{
    Authenticatable,
    MustVerifyEmail,
    Passwords\CanResetPassword
};
use Arr;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class User extends Model implements
    ActivatableInterface,
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    WithImage
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
        HasPictureAttribute,
        Activatable,
        Searchable,
        SoftDeletes,
        HasImage,
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
        'password', 'remember_token', 'roles', 'updated_at', 'deleted_at', 'image'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    protected static $logAttributes = [
        'first_name', 'middle_name', 'last_name', 'email', 'phone', 'role.name', 'timezone.text'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function companies(): HasManyDeep
    {      
        return $this->hasManyDeep(
            Company::class,
            ['model_has_roles', Role::class, ModelHasRoles::class . ' as model_roles'],
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
        return $query->whereDoesntHave('roles', fn ($query) => $query->whereName('Administrator'));
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

    public function imagesDirectory(): string
    {
        return "images/users";
    }

    public function toSearchArray()
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
}
