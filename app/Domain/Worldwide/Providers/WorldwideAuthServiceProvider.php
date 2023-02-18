<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Policies\OpportunityPolicy;
use App\Domain\Worldwide\Policies\SalesOrderPolicy;
use App\Domain\Worldwide\Policies\WorldwideDistributionPolicy;
use App\Domain\Worldwide\Policies\WorldwideQuotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WorldwideAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(WorldwideQuote::class, WorldwideQuotePolicy::class);
        Gate::policy(WorldwideDistribution::class, WorldwideDistributionPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
    }
}
