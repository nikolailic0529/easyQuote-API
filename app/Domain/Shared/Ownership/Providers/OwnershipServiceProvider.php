<?php

namespace App\Domain\Shared\Ownership\Providers;

use App\Domain\Shared\Ownership\ChangeOwnershipStrategyCollection;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class OwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(ChangeOwnershipStrategyCollection::class)
            ->needs(ChangeOwnershipStrategy::class)
            ->giveTagged(ChangeOwnershipStrategy::class);
    }
}
