<?php

namespace App\Domain\UnifiedQuote\Providers;

use App\Domain\UnifiedQuote\Policies\UnifiedQuotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class UnifiedQuoteAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewQuotesOfAnyBusinessDivision',
            [UnifiedQuotePolicy::class, 'viewEntitiesOfAnyBusinessDivision']);
        Gate::define('viewQuotesOfAnyUser', [UnifiedQuotePolicy::class, 'viewEntitiesOfAnyUser']);
    }
}
