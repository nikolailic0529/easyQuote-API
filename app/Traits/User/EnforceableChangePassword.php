<?php

namespace App\Traits\User;

use App\Observers\PasswordObserver;

trait EnforceableChangePassword
{
    protected static function bootEnforceableChangePassword()
    {
        static::observe(PasswordObserver::class);
    }

    protected function initializeEnforceableChangePassword()
    {
        $this->casts = array_merge($this->casts, ['password_changed_at' => 'datetime']);
        $this->fillable = array_merge($this->fillable, ['password_changed_at']);
    }

    public function mustChangePassword(): bool
    {
        return is_null($this->password_changed_at) ||
            now()->endOfDay()->diffInDays($this->password_changed_at) >= ENF_PWD_CHANGE_DAYS;
    }

    public function getMustChangePasswordAttribute(): bool
    {
        return $this->mustChangePassword();
    }

    public function enforceChangePassword(): bool
    {
        return $this->forceFill(['password_changed_at' => null])->save();
    }
}
