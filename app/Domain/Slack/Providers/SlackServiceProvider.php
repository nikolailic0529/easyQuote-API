<?php

namespace App\Domain\Slack\Providers;

use App\Domain\Slack\Contracts\SlackInterface;
use App\Domain\Slack\Services\SlackClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SlackServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SlackInterface::class, SlackClient::class);

        $this->app->alias(SlackInterface::class, 'slack.client');
    }

    public function provides()
    {
        return [
            SlackInterface::class,
            'slack.client',
        ];
    }
}
