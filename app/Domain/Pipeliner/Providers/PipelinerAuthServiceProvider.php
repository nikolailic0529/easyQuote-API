<?php

namespace App\Domain\Pipeliner\Providers;

use App\Domain\Pipeliner\Models\PipelinerSyncError;
use App\Domain\Pipeliner\Policies\PipelinerSyncErrorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PipelinerAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(PipelinerSyncError::class, PipelinerSyncErrorPolicy::class);
    }
}
