<?php

namespace App\Traits\Auth;

use Laravel\Passport\{
    Passport,
    HasApiTokens as PassportHasApiTokens
};

trait HasApiTokens
{
    use PassportHasApiTokens;

    /**
     * Latest NonExpired Token in Use.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tokenInUse()
    {
        return $this->hasOne(Passport::tokenModel())->latest()->whereRevoked(false);
    }

    /**
     * Determine that User is already logged in.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return !is_null($this->tokenInUse);
    }
}
