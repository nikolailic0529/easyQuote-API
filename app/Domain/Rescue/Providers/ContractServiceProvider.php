<?php

namespace App\Domain\Rescue\Providers;

use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Observers\ContractObserver;
use Illuminate\Support\ServiceProvider;

class ContractServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Contract::observe(ContractObserver::class);
    }
}
