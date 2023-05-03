<?php

namespace App\Domain\Rescue\Providers;

use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Policies\ContractPolicy;
use App\Domain\Rescue\Policies\CustomerPolicy;
use App\Domain\Rescue\Policies\QuotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class RescueAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
