<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Worldwide\Contracts\ProcessesWorldwideDistributionState;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteAssetState;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteState;
use App\Domain\Worldwide\Queries\WorldwideQuoteQueries;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideDistributionStateProcessor;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteAssetStateProcessor;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteStateProcessor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class QuoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProcessesWorldwideQuoteState::class, WorldwideQuoteStateProcessor::class);

        $this->app->singleton(WorldwideQuoteQueries::class);

        $this->app->singleton(ProcessesWorldwideDistributionState::class, function (Container $container) {
            $storage = $container['filesystem']->disk('ww_quote_files');

            return $container->make(WorldwideDistributionStateProcessor::class, ['storage' => $storage]);
        });

        $this->app->singleton(ProcessesWorldwideQuoteAssetState::class, function (Container $container) {
            $storage = $container['filesystem']->disk('ww_asset_files');

            return $container->make(WorldwideQuoteAssetStateProcessor::class, ['storage' => $storage]);
        });
    }
}
