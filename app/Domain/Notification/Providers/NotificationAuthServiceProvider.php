<?php

namespace App\Domain\Notification\Providers;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Policies\NotificationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NotificationAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Notification::class, NotificationPolicy::class);
    }
}
