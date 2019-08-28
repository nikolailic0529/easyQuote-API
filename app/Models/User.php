<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use App\Models\AuthenticableUser;
use App\Traits\HasCountry;
use App\Traits\HasTimezone;
use App\Traits\HasRole;
use App\Traits\CanBeAdmin;

class User extends AuthenticableUser implements MustVerifyEmail
{
    use HasRole, HasApiTokens, Notifiable, HasCountry, HasTimezone, CanBeAdmin;

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
}
