<?php

namespace App\Providers;

use App\Contracts\LoggerAware;
use App\Http\Client\LoggingClientFactory;
use App\Services\DocumentEngine\MappingClient;
use App\Services\DocumentEngine\OauthClient;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as ClientFactory;
use Illuminate\Support\ServiceProvider;

class DocumentEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving(OauthClient::class, function (OauthClient $concrete) {
            if ($concrete instanceof LoggerAware) {
                $concrete->setLogger(
                    $this->app['log']->channel('document-engine-api')
                );
            }
        });

        $this->app->afterResolving(MappingClient::class, function (MappingClient $concrete) {
            if ($concrete instanceof LoggerAware) {
                $concrete->setLogger(
                    $this->app['log']->channel('document-engine-api')
                );
            }
        });

        $this->app->bind('document-engine-api::client', function (Container $container) {
            return tap($container->make(LoggingClientFactory::class), function (LoggingClientFactory $factory) use ($container) {
                if ($factory instanceof LoggerAware) {
                    $factory->setLogger(
                        $container['log']->channel('document-engine-api')
                    );
                }
            });
        });

        $this->app->when([OauthClient::class, MappingClient::class])->needs(ClientFactory::class)->give('document-engine-api::client');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
