<?php

namespace App\Traits\User;

use Illuminate\Database\Eloquent\Model;

trait EnforceableChangePassword
{
    protected $oldPassword;

    protected function initializeEnforceableChangePassword()
    {
        $this->casts = array_merge($this->casts, ['password_changed_at' => 'datetime']);
        $this->fillable = array_merge($this->fillable, ['password_changed_at']);

        static::creating(function (Model $model) {
            $model->password_changed_at = now()->startOfDay();
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('password')) {
                $model->password_changed_at = now()->startOfDay();
            }
        });
    }

    public function mustChangePassword(): bool
    {
        return is_null($this->password_changed_at) ||
            now()->startOfDay()->diffInDays($this->password_changed_at) >= ENF_PWD_CHANGE_DAYS;
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
