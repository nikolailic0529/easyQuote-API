<?php

namespace App\Models;

use App\Contracts\{
    ActivatableInterface,
    WithImage
};
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\{
    Role,
    AuthenticableUser,
    Collaboration\Invitation
};
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
    Notifiable
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class User extends AuthenticableUser implements MustVerifyEmail, ActivatableInterface, WithImage
{
    use HasRoles,
        HasImportableColumns,
        HasQuotes,
        HasQuoteFiles,
        HasQuoteFilesDirectory,
        HasApiTokens,
        HasCountry,
        HasInvitations,
        Notifiable,
        BelongsToTimezone,
        HasCountryMargins,
        HasDiscounts,
        HasVendors,
        HasCompanies,
        HasQuoteTemplates,
        HasTemplateFields,
        HasPictureAttribute,
        Activatable,
        Searchable,
        SoftDeletes,
        HasImage,
        // LogsActivity,
        Loginable,
        PerformsActivity,
        EnforceableChangePassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'timezone_id', 'email', 'password', 'role_id', 'phone', 'default_route', 'recent_notifications_limit'
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
        return $this->roles->first(null, Role::make([]));
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

    public function withAppends(...$attributes)
    {
        $appends = ['role_id', 'role_name', 'picture', 'privileges', 'role_properties', 'must_change_password'];
        return $this->append(array_merge($appends, $attributes));
    }
}
