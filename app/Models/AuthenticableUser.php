<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Auth\{
    Authenticatable,
    MustVerifyEmail,
    Passwords\CanResetPassword
};
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\{
    Authenticatable as AuthenticatableContract,
    Access\Authorizable as AuthorizableContract,
    CanResetPassword as CanResetPasswordContract
};

class AuthenticableUser extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
}
