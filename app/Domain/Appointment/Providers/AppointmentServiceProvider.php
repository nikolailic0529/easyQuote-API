<?php

namespace App\Domain\Appointment\Providers;

use App\Domain\Appointment\Listeners\AppointmentEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class AppointmentServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        AppointmentEventAuditor::class,
    ];
}
