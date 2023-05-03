<?php

namespace App\Domain\Contact\Providers;

use App\Domain\Contact\Services\ContactOwnershipService;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class ContactOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(ContactOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}
