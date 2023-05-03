<?php

namespace App\Domain\Company\Providers;

use App\Domain\Company\Services\CompanyOwnershipService;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class CompanyOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(CompanyOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}
