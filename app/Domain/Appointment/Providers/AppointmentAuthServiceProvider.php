<?php

namespace App\Domain\Appointment\Providers;

use App\Domain\Appointment\Models\AppointmentReminder;
use App\Domain\Appointment\Policies\AppointmentReminderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppointmentAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(AppointmentReminder::class, AppointmentReminderPolicy::class);
    }
}
