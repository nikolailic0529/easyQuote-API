<?php

namespace App\Domain\HpeContract\Providers;

use App\Domain\HpeContract\Contracts\HpeContractState;
use App\Domain\HpeContract\Contracts\{HpeExporter};
use App\Domain\HpeContract\Services\HpeContractExporter;
use App\Domain\HpeContract\Services\{HpeContractStateProcessor};
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
