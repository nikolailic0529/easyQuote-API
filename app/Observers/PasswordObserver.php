<?php

namespace App\Observers;

use App\Notifications\PasswordChanged;
use Illuminate\Database\Eloquent\Model;

class PasswordObserver
{

    /**
     * Handle the model "creating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function creating(Model $model)
    {
        $model->password_changed_at = now()->startOfDay();
    }

    /**
     * Handle the model "updating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updating(Model $model)
    {
        if ($model->isDirty('password')) {
            $model->password_changed_at = now()->startOfDay();
        }
    }

    /**
     * Handle the model "updating" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updated(Model $model)
    {
        if (!$model->wasChanged('password')) {
            return;
        }

        $model->notify(new PasswordChanged);

        notification()
            ->for($model)
            ->message(PWDC_01)
            ->url(ui_route('users.profile'))
            ->subject($model)
            ->priority(2)
            ->queue();
    }
}