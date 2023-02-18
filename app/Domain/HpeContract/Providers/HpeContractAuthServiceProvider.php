<?php

namespace App\Domain\HpeContract\Providers;

use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\HpeContract\Policies\HpeContractPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class HpeContractAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(HpeContract::class, HpeContractPolicy::class);
    }
}
