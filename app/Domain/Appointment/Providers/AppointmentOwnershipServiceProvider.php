<?php

namespace App\Domain\Appointment\Providers;

use App\Domain\Appointment\Services\AppointmentOwnershipService;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class AppointmentOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(AppointmentOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}
