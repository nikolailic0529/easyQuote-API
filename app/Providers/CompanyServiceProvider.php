<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Repositories\CompanyRepository;

class CompanyServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CompanyRepositoryInterface::class, CompanyRepository::class);

        $this->app->alias(CompanyRepositoryInterface::class, 'company.repository');
    }

    public function provides()
    {
        return [
            CompanyRepositoryInterface::class,
            'company.repository',
        ];
    }
}
