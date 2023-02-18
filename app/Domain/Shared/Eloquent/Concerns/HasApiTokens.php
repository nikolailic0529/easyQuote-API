<?php

namespace App\Domain\Shared\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passport\HasApiTokens as LaravelHasApiTokens;

trait HasApiTokens
{
    use LaravelHasApiTokens;

    public function nonExpiredTokens(): HasMany
    {
        return $this->tokens()
            ->where('expires_at', '>', now())
            ->where('revoked', false);
    }

    public function hasNonExpiredTokens(): bool
    {
        return $this->nonExpiredTokens()->exists();
    }

    public function doesntHaveNonExpiredTokens(): bool
    {
        return !$this->hasNonExpiredTokens();
    }

    public function revokeTokens(): bool
    {
        return (bool) $this->tokens()->update(['revoked' => true]);
    }
}
