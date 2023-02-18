<?php

namespace App\Domain\User\Observers;

use App\Domain\Priority\Enum\Priority;
use App\Domain\User\Notifications\PasswordChanged;
use App\Foundation\Mail\Exceptions\MailRateLimitException;
use Illuminate\Database\Eloquent\Model;

class PasswordObserver
{
    /**
     * Handle the model "creating" event.
     *
     * @return void
     */
    public function creating(Model $model)
    {
        $model->password_changed_at = now()->startOfDay();
    }

    /**
     * Handle the model "updating" event.
     *
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
     * @return void
     */
    public function updated(Model $model)
    {
        if (!$model->wasChanged('password')) {
            return;
        }

        try {
            $model->notify(new PasswordChanged());
        } catch (MailRateLimitException $e) {
            report($e);
        }

        notification()
            ->for($model)
            ->message(PWDC_01)
            ->url(ui_route('users.profile'))
            ->subject($model)
            ->priority(Priority::Medium)
            ->queue();
    }
}
