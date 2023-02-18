<?php

namespace App\Domain\Discount\Providers;

use App\Domain\Discount\Contracts\MultiYearDiscountRepositoryInterface;
use App\Domain\Discount\Contracts\PrePayDiscountRepositoryInterface;
use App\Domain\Discount\Contracts\PromotionalDiscountRepositoryInterface;
use App\Domain\Discount\Contracts\SNDrepositoryInterface;
use App\Domain\Discount\Repositories\MultiYearDiscountRepository;
use App\Domain\Discount\Repositories\PrePayDiscountRepository;
use App\Domain\Discount\Repositories\PromotionalDiscountRepository;
use App\Domain\Discount\Repositories\SNDrepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
