<?php

namespace App\Domain\Invitation\Providers;

use App\Domain\Invitation\Contracts\InvitationRepositoryInterface;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\Invitation\Observers\InvitationObserver;
use App\Domain\Invitation\Repositories\InvitationRepository;
use Illuminate\Support\ServiceProvider;

class InvitationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvitationRepositoryInterface::class, InvitationRepository::class);
    }

    public function boot(): void
    {
        Invitation::observe(InvitationObserver::class);
    }

    public function provides(): array
    {
        return [
            InvitationRepositoryInterface::class,
        ];
    }
}
