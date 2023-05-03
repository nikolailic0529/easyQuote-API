<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Worldwide\Services\Opportunity\OpportunityOwnershipService;
use Illuminate\Support\ServiceProvider;

class OpportunityOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(OpportunityOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}
