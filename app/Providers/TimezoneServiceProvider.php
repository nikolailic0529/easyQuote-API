<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\TimezoneRepositoryInterface;
use App\Repositories\TimezoneRepository;

class TimezoneServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TimezoneRepositoryInterface::class, TimezoneRepository::class);

        $this->app->alias(TimezoneRepositoryInterface::class, 'timezone.repository');
    }

    public function provides()
    {
        return [
            TimezoneRepositoryInterface::class,
            'timezone.repository',
        ];
    }
}
