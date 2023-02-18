<?php

namespace App\Domain\Invitation\Providers;

use App\Domain\Invitation\Models\Invitation;
use App\Domain\Invitation\Policies\InvitationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class InvitationAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Invitation::class, InvitationPolicy::class);
    }
}
