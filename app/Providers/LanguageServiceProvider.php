<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\LanguageRepositoryInterface;
use App\Repositories\LanguageRepository;

class LanguageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LanguageRepositoryInterface::class, LanguageRepository::class);
    }

    public function provides()
    {
        return [
            LanguageRepositoryInterface::class,
        ];
    }
}
