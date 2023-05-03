<?php

namespace App\Domain\VendorServices\Services;

use App\Domain\VendorServices\DataTransferObjects\WarrantyLookupResult;
use App\Domain\VendorServices\Exceptions\ServiceLookupRouteException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\{Str};
use Psr\SimpleCache\InvalidArgumentException;

class WarrantyLookupService
{
    const SERVICE_RESPONSE_CACHE_KEY = 'service_lookup.response';
    const SERVICE_RESPONSE_CACHE_TTL = 15 * 60; // 15 minutes

    const PLH_SERIAL = '{serial}';
    const PLH_SKU = '{sku}';
    const PLH_COUNTRY = '{country}';

    const RESP_FAIL = null;

    public function __construct(protected Config $config,
                                protected Cache $cache,
                                protected CachingOauthClient $oauthClient,
                                protected HttpFactory $http)
    {
    }

    /**
     * Get service data by specific parameters.
     *
     * @throws \App\Domain\VendorServices\Exceptions\ServiceLookupRouteException
     * @throws InvalidArgumentException
     */
    public function getWarranty(string $vendorCode,
                                string $serial,
                                ?string $sku = null,
                                ?string $countryCode = null): ?WarrantyLookupResult
    {
        $url = (string) Str::of($this->getBaseUrl())
            ->append($this->resolveVendorRoute($vendorCode))
            ->replace(
                [self::PLH_SERIAL, self::PLH_SKU, self::PLH_COUNTRY],
                [$serial, $sku, $countryCode]
            );

        $cacheKey = self::cacheKeyOfUrl($url);

        $resultFromCache = $this->cache->get($cacheKey);

        if (!is_null($resultFromCache)) {
            \customlog(['message' => SL_CRE_01], ['url' => $url, 'cache_ttl' => self::SERVICE_RESPONSE_CACHE_TTL]);

            return $resultFromCache;
        }

        \customlog(['message' => sprintf(SL_REQ_01, $url)], ['parameters' => ['vendor' => $vendorCode, 'serial' => $serial, 'sku' => $sku]]);

        $pendingRequest = $this->http
            ->withToken($this->oauthClient->getAccessToken())
            ->acceptJson();

        $response = (clone $pendingRequest)->get($url);

        if ($this->shouldRefreshAccessToken($response)) {
            $token = $this->oauthClient
                ->forgetAccessToken()
                ->getAccessToken();

            $response = (clone $pendingRequest)
                ->withToken($token)
                ->get($url);
        }

        if ($response->failed()) {
            \customlog(['ErrorCode' => 'SL_UR_01'], [
                'ErrorDetails' => SL_UR_01,
                'Exception' => "HTTP request returned status code {$response->status()}.",
                'Response' => $response->json(),
            ]);

            return self::RESP_FAIL;
        }

        return \tap(WarrantyLookupResult::fromArray($response->json()), function (WarrantyLookupResult $data) use ($cacheKey) {
            $this->cache->put($cacheKey, $data, self::SERVICE_RESPONSE_CACHE_TTL);
        });
    }

    protected function shouldRefreshAccessToken(HttpResponse $response): bool
    {
        if (false === $response->failed()) {
            return false;
        }

        $responseMessage = $response->json('Error.original.message');

        return is_string($responseMessage) &&
            str_contains(strtolower($responseMessage), 'unauthenticated');
    }

    /**
     * @throws \App\Domain\VendorServices\Exceptions\ServiceLookupRouteException
     */
    protected function resolveVendorRoute(string $vendorName): string
    {
        $route = $this->config->get("services.vs.service_routes.$vendorName");

        if (is_null($route)) {
            throw ServiceLookupRouteException::unsupportedVendorRoute($vendorName);
        }

        return $route;
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->config->get('services.vs.url'), '/').'/';
    }

    protected static function cacheKeyOfUrl(string $url): string
    {
        return static::SERVICE_RESPONSE_CACHE_KEY.'::'.$url;
    }
}
