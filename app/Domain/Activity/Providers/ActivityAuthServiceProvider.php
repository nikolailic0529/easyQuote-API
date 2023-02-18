<?php

namespace App\Domain\Activity\Providers;

use App\Domain\Activity\Models\Activity;
use App\Domain\Activity\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ActivityAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
