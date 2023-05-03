<?php

namespace App\Domain\Discount\Providers;

use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Discount\Policies\MultiYearDiscountPolicy;
use App\Domain\Discount\Policies\PrePayDiscountPolicy;
use App\Domain\Discount\Policies\PromotionalDiscountPolicy;
use App\Domain\Discount\Policies\SNDPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DiscountAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SND::class, SNDPolicy::class);
        Gate::policy(PrePayDiscount::class, PrePayDiscountPolicy::class);
        Gate::policy(MultiYearDiscount::class, MultiYearDiscountPolicy::class);
        Gate::policy(PromotionalDiscount::class, PromotionalDiscountPolicy::class);
    }
}
