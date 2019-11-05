<?php namespace App\Models;

use App\Contracts\ActivatableInterface;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use App\Models \ {
    Role,
    AuthenticableUser,
    Collaboration\Invitation
};
use App\Traits \ {
    Activatable,
    HasCountry,
    HasTimezone,
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
    Search\Searchable
};
use Spatie\Permission\Traits\HasRoles;

class User extends AuthenticableUser implements MustVerifyEmail, ActivatableInterface
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
        HasTimezone,
        HasCountryMargins,
        HasDiscounts,
        HasVendors,
        HasCompanies,
        HasQuoteTemplates,
        HasTemplateFields,
        Activatable,
        Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'timezone_id', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'roles'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'role_id', 'role_name'
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";
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
        if($model instanceof Invitation) {
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
}
