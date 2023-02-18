<?php

namespace App\Domain\Address\Providers;

use App\Domain\Address\Services\AddressOwnershipService;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class AddressOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(AddressOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}
