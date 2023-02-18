<?php

namespace App\Domain\Margin\Providers;

use App\Domain\Margin\Contracts\MarginRepositoryInterface;
use App\Domain\Margin\Repositories\MarginRepository;
use Illuminate\Support\ServiceProvider;

class MarginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MarginRepositoryInterface::class, MarginRepository::class);
        $this->app->alias(MarginRepositoryInterface::class, 'margin.repository');
    }
}
