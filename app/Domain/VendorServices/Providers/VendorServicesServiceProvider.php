<?php

namespace App\Domain\VendorServices\Providers;

use App\Domain\VendorServices\Services\OauthClient;
use App\Domain\Worldwide\Services\SalesOrder\CancelSalesOrderService;
use App\Domain\Worldwide\Services\SalesOrder\SubmitSalesOrderService;
use App\Foundation\Http\Client\LoggingClient\LoggingClientFactory;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as ClientFactory;
use Illuminate\Support\ServiceProvider;

class VendorServicesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('vendor-services-api::client', function (Container $container) {
            return tap($container->make(LoggingClientFactory::class), function (LoggingClientFactory $factory) use ($container) {
                if ($factory instanceof LoggerAware) {
                    $factory->setLogger(
                        $container['log']->channel('vendor-services-requests')
                    );
                }
            });
        });

        $this->app->when([OauthClient::class, CancelSalesOrderService::class, SubmitSalesOrderService::class])->needs(ClientFactory::class)->give('vendor-services-api::client');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
