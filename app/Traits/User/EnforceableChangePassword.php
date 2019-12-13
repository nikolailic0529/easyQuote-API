<?php

namespace App\Traits\User;

use Illuminate\Database\Eloquent\Model;

trait EnforceableChangePassword
{
    protected $oldPassword;

    protected function initializeEnforceableChangePassword()
    {
        $this->casts = array_merge($this->casts, ['password_changed_at' => 'datetime']);

        static::creating(function (Model $model) {
            $model->password_changed_at = now();
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('password')) {
                $model->password_changed_at = now();
            }
        });
    }

    public function mustChangePassword(): bool
    {
        return is_null($this->password_changed_at) || now()->diffInDays($this->password_changed_at) >= 90;
    }
}
