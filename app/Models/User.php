<?php namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use App\Models\AuthenticableUser;
use App\Models\Collaboration\Invitation;
use App\Traits \ {
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
    Collaboration\BelongsToCollaboration,
    Collaboration\HasInvitations
};
use Spatie\Permission\Traits\HasRoles;

class User extends AuthenticableUser implements MustVerifyEmail
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
        BelongsToCollaboration;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'country_id', 'timezone_id', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
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
            $this->attributes['collaboration_id'] = $model->collaboration_id;
            $this->assignRole($model->role);
            return $this->save() && $model->delete();
        }
    }
}
