<?php

namespace App\Domain\Company\Providers;

use App\Domain\Company\Models\Company;
use App\Domain\Company\Policies\CompanyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CompanyAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);
    }
}
