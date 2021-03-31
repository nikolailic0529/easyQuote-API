<?php

namespace App\Providers;

use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EntityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            '6c0f3f29-2d00-4174-9ef8-55aa5889a812' => Quote::class,
            '4d6833e8-d018-4934-bfae-e8587f7aec51' => WorldwideQuote::class,
            '9d7c91c4-5308-4a40-b49e-f10ae552e480' => WorldwideQuoteVersion::class,
        ]);
    }
}
