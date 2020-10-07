<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\SlackInterface;
use App\Services\SlackClient;

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
