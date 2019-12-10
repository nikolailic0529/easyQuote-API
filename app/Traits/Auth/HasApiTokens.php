<?php

namespace App\Traits\Auth;

use Laravel\Passport\HasApiTokens as LaravelHasApiTokens;

trait HasApiTokens
{
    use LaravelHasApiTokens;

    public function nonExpiredTokens()
    {
        return $this->tokens()->where('expires_at', '>', now())->whereRevoked(false);
    }

    public function hasNonExpiredTokens()
    {
        return $this->nonExpiredTokens()->exists();
    }

    public function doesntHaveNonExpiredTokens()
    {
        return !$this->hasNonExpiredTokens();
    }

    public function revokeTokens()
    {
        return $this->tokens()->update(['revoked' => true]);
    }
}
