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
     * NonExpired Tokens in Use.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tokensInUse()
    {
        return $this->tokens()->where('expires_at', '>', now())->whereRevoked(false);
    }

    /**
     * Determine that User is already logged in.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->tokensInUse()->exists();
    }
}
