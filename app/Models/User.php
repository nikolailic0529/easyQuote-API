<?php

namespace App\Models;

use App\Contracts\{
    ActivatableInterface,
    WithImage
};
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
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
    Image\HasPictureAttribute
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
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
        LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'timezone_id', 'email', 'password', 'role_id', 'phone'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'roles', 'updated_at', 'deleted_at', 'privileges', 'image'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'must_change_password' => 'boolean'
    ];

    protected $appends = [
        'role_id', 'role_name', 'picture', 'privileges'
    ];

    protected static $logAttributes = [
        'first_name', 'middle_name', 'last_name', 'email', 'password', 'phone', 'role.name', 'timezone.text'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeAdministrators($query)
    {
        return $query->role('Administrator');
    }

    public function scopeNonAdministrators($query)
    {
        return $query->whereDoesntHave('roles', function ($query) {
            $query->whereName('Administrator');
        });
    }

    public function interact($model)
    {
        if ($model instanceof Invitation) {
            $this->assignRole($model->role);
            return $this->save() && $model->delete();
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
}
