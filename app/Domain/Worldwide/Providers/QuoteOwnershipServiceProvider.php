<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteOwnershipService;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteVersionOwnershipService;
use Illuminate\Support\ServiceProvider;

class QuoteOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            WorldwideQuoteOwnershipService::class,
            WorldwideQuoteVersionOwnershipService::class,
        ], ChangeOwnershipStrategy::class);
    }
}
