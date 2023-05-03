<?php

namespace App\Domain\User\Observers;

use App\Domain\User\Notifications\PasswordChangedNotification;
use Illuminate\Database\Eloquent\Model;

class PasswordObserver
{
    public function creating(Model $model): void
    {
        $model->password_changed_at = now()->startOfDay();
    }

    public function updating(Model $model): void
    {
        if ($model->isDirty('password')) {
            $model->password_changed_at = now()->startOfDay();
        }
    }

    public function updated(Model $model): void
    {
        if (!$model->wasChanged('password')) {
            return;
        }

        rescue(static function () use ($model): void {
            $model->notify(new PasswordChangedNotification());
        });
    }
}
