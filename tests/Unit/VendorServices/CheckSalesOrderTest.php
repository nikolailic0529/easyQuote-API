<?php

namespace Tests\Unit\VendorServices;

use App\Services\VendorServices\CheckSalesOrderService;
use App\Services\VendorServices\Exceptions\VendorServicesRequestException;
use App\Services\VendorServices\Models\CheckSalesOrderResult;
use App\Services\VendorServices\OauthClient as VSOauthClient;
use Illuminate\Http\Client\Factory as HttpFactory;
use Tests\TestCase;

class CheckSalesOrderTest extends TestCase
{
    /**
     * Test handling of successful response of check sales order endpoint.
     *
     * @return void
     * @throws \App\Services\VendorServices\Exceptions\VendorServicesRequestException
     */
    public function testItChecksExistingSalesOrderOnVendorServicesApi()
    {
        $response = json_decode(file_get_contents(__DIR__.'/../Data/vendor-services/check-sales-order-success-response.json'), true);

        /** @var HttpFactory $oauthFactory */
        $oauthFactory = $this->app[HttpFactory::class];

        $oauthFactory->fake([
            '*' => HttpFactory::response(['token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234']),
        ]);

        $this->app->when(VSOauthClient::class)->needs(HttpFactory::class)->give(fn() => $oauthFactory);

        /** @var HttpFactory $factory */
        $factory = $this->app[HttpFactory::class];

        $factory->fake([
            '*' => $factory->response($response)
        ]);

        $this->app->when(CheckSalesOrderService::class)
            ->needs(HttpFactory::class)
            ->give(fn() => $factory);

        /** @var CheckSalesOrderService $service */
        $service = $this->app[CheckSalesOrderService::class];

        $response = $service->checkSalesOrder('20fb9858-4f04-4371-80cd-30bde8d79aad');

        $this->assertInstanceOf(CheckSalesOrderResult::class, $response);
    }

    /**
     * Test handling of failed response of check sales order endpoint.
     *
     * @return void
     */
    public function testItThrowsExceptionWhenMissingSalesOrderOnVendorServicesApi()
    {
        $response = json_decode(file_get_contents(__DIR__.'/../Data/vendor-services/check-sales-order-failure-response.json'), true);

        /** @var HttpFactory $oauthFactory */
        $oauthFactory = $this->app[HttpFactory::class];

        $oauthFactory->fake([
            '*' => HttpFactory::response(['token_type' => 'Bearer', 'expires_in' => 31536000, 'access_token' => '1234']),
        ]);

        $this->app->when(VSOauthClient::class)->needs(HttpFactory::class)->give(fn() => $oauthFactory);

        /** @var HttpFactory $factory */
        $factory = $this->app[HttpFactory::class];

        $factory->fake([
            '*' => $factory->response($response, 422)
        ]);

        $this->app->when(CheckSalesOrderService::class)
            ->needs(HttpFactory::class)
            ->give(fn() => $factory);

        /** @var CheckSalesOrderService $service */
        $service = $this->app[CheckSalesOrderService::class];

        $this->expectException(VendorServicesRequestException::class);

        $service->checkSalesOrder('20fb9858-4f04-4371-80cd-30bde8d79aad');
    }
}
