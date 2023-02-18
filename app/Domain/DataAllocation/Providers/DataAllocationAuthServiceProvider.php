<?php

namespace App\Domain\DataAllocation\Providers;

use App\Domain\DataAllocation\Models\DataAllocation;
use App\Domain\DataAllocation\Policies\DataAllocationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DataAllocationAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(DataAllocation::class, DataAllocationPolicy::class);
    }
}
