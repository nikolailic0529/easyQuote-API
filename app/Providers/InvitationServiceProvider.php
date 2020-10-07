<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\InvitationRepositoryInterface;
use App\Repositories\InvitationRepository;

class InvitationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(InvitationRepositoryInterface::class, InvitationRepository::class);
    }

    public function provides()
    {
        return [
            InvitationRepositoryInterface::class,
        ];
    }
}
