<?php

namespace App\Domain\App\Providers;

use App\Domain\App\View\Components\AppLayout;
use App\Domain\App\View\Components\GuestLayout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::component(AppLayout::class);
        Blade::component(GuestLayout::class);
    }
}
