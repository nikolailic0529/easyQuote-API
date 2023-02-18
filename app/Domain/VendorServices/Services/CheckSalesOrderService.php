<?php

namespace App\Domain\VendorServices\Services;

use App\Domain\VendorServices\Services\Exceptions\VendorServicesRequestException;
use App\Domain\VendorServices\Services\Models\CheckSalesOrderResult;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;

class CheckSalesOrderService
{
    public function __construct(protected Config $config,
                                protected Cache $cache,
                                protected CachingOauthClient $oauthClient,
                                protected HttpFactory $http)
    {
    }

    /**
     * @throws VendorServicesRequestException
     */
    public function checkSalesOrder(string $id): CheckSalesOrderResult
    {
        $url = $this->buildCheckSalesOrderUrl($id);

        $response = $this->http->acceptJson()
            ->withToken($this->oauthClient->getAccessToken())
            ->get($url);

        if ($response->failed()) {
            throw new VendorServicesRequestException(response: $response, previous: $response->toException());
        }

        return $this->buildCheckSalesOrderResult($response->json());
    }

    protected function buildCheckSalesOrderResult(array $data): CheckSalesOrderResult
    {
        $floatCaster = static function (mixed $value): ?float {
            if (is_null($value)) {
                return null;
            }

            return (float) $value;
        };

        $data['bc_orders'] = collect($data['bc_orders'] ?? [])
            ->map(function (array $orderData) use ($floatCaster) {
                $orderData['exchange_rate'] = $floatCaster($orderData['exchange_rate'] ?? null);

                $orderData['bc_sales_line'] = collect($orderData['bc_sales_line'] ?? [])
                    ->map(function (array $lineData) use ($floatCaster) {
                        foreach (['unit_price', 'buy_price', 'discount_percentage', 'vat_amount'] as $key) {
                            $lineData[$key] = $floatCaster($lineData[$key] ?? null);
                        }

                        return $lineData;
                    })
                    ->all();

                return $orderData;
            })
            ->all();

        return new CheckSalesOrderResult($data);
    }

    protected function buildCheckSalesOrderUrl(string $id): string
    {
        $resource = strtr($this->config->get('services.vs.check_sales_order_route'), ['{id}' => $id]);

        return rtrim($this->getBaseUrl(), '/').'/'.trim($resource, '/');
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->config->get('services.vs.url'), '/').'/';
    }
}
