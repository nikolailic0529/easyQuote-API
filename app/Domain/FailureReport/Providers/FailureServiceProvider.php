<?php

namespace App\Domain\FailureReport\Providers;

use App\Domain\FailureReport\Contracts\RateLimiter;
use App\Domain\FailureReport\DefaultRateLimiter;
use App\Domain\FailureReport\MailErrorReporter;
use App\Foundation\Error\ErrorReporter;
use Illuminate\Support\ServiceProvider;

class FailureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ErrorReporter::class, MailErrorReporter::class);

        $this->app->bind(RateLimiter::class, DefaultRateLimiter::class);
    }
}
