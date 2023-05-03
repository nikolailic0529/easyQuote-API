<?php

namespace App\Domain\Mail\Providers;

use App\Domain\Mail\Models\MailLog;
use App\Domain\Mail\Policies\MailLogPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class MailLogAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(MailLog::class, MailLogPolicy::class);
    }
}
