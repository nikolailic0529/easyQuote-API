<?php

namespace App\Domain\Shared\Horizon\Providers;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', static function (User $user): bool {
            return $user->hasRole(R_SUPER);
        });
    }
}
