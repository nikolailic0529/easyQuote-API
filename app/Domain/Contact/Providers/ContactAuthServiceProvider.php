<?php

namespace App\Domain\Contact\Providers;

use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Policies\ContactPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ContactAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Contact::class, ContactPolicy::class);
    }
}
