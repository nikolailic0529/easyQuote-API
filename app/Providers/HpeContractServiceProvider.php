<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\{HpeContractState, HpeExporter};
use App\Services\{HpeContractExporter, HpeContractStateProcessor};

class HpeContractServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(HpeExporter::class, HpeContractExporter::class);

        $this->app->singleton(HpeContractState::class, HpeContractStateProcessor::class);
    }

    public function provides()
    {
        return [
            HpeExporter::class,
            HpeContractState::class,
        ];
    }
}
