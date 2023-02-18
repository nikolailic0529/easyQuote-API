<?php

namespace App\Foundation\Support\Providers;

use App\Foundation\Support\Mixins\ArrMixin;
use App\Foundation\Support\Mixins\CarbonMixin;
use App\Foundation\Support\Mixins\CollectionMixin;
use App\Foundation\Support\Mixins\StrMixin;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Str::mixin(new StrMixin());

        Collection::mixin(new CollectionMixin());

        Carbon::mixin(new CarbonMixin());

        Arr::mixin(new ArrMixin());
    }
}
