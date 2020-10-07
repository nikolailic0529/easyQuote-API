<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\Quote\Discount\{MultiYearDiscountRepositoryInterface, PrePayDiscountRepositoryInterface, PromotionalDiscountRepositoryInterface, SNDrepositoryInterface};
use App\Repositories\Quote\Discount\{MultiYearDiscountRepository, PrePayDiscountRepository, PromotionalDiscountRepository, SNDrepository};

class DiscountServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MultiYearDiscountRepositoryInterface::class, MultiYearDiscountRepository::class);

        $this->app->singleton(PrePayDiscountRepositoryInterface::class, PrePayDiscountRepository::class);

        $this->app->singleton(PromotionalDiscountRepositoryInterface::class, PromotionalDiscountRepository::class);

        $this->app->singleton(SNDrepositoryInterface::class, SNDrepository::class);
    }

    public function provides()
    {
        return [
            MultiYearDiscountRepositoryInterface::class,
            PrePayDiscountRepositoryInterface::class,
            PromotionalDiscountRepositoryInterface::class,
            SNDrepositoryInterface::class,
        ];
    }
}
