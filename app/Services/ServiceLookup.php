<?php

namespace App\Services;

use App\DTO\ServiceData;
use App\Models\Vendor;
use App\Services\Exceptions\ServiceLookupRoute;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Response;
use Illuminate\Support\{
    Str,
    Arr,
    Facades\Http,
};
use Throwable;

class ServiceLookup
{
    protected const SERVICE_TOKEN_CACHE_KEY = 'service_lookup.token';

    protected const SERVICE_RESPONSE_CACHE_KEY = 'service_lookup.response';

    protected const SERVICE_RESPONSE_CACHE_TTL = 15 * 60; // 15 minutes

    protected const ROUTE_SERIAL_KEY = '{serial}';

    protected const ROUTE_SKU_KEY = '{sku}';

    protected const INV_RESPONSE = null;

    protected Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function getService(Vendor $vendor, string $serial, ?string $sku = null): ?ServiceData
    {
        $url = Str::of(static::serviceBaseUrl())
            ->append(static::resolveVendorRoute($vendor->short_code))
            ->replace(
                [static::ROUTE_SERIAL_KEY, static::ROUTE_SKU_KEY],
                [$serial, $sku]
            );

        $cacheKey = static::serviceResponseCacheKey((string) $url);

        if ($this->cache->has($cacheKey)) {
            report_logger(['message' => SL_CRE_01], ['url' => (string) $url, 'cache_ttl' => static::SERVICE_RESPONSE_CACHE_TTL]);

            return $this->cache->get($cacheKey);
        }

        report_logger(['message' => sprintf(SL_REQ_01, (string) $url)], ['parameters' => ['vendor' => $vendor->short_code, 'serial' => $serial, 'sku' => $sku]]);

        $response = Http::withToken($this->issueServiceToken())->get((string) $url);

        /**
         * Hydrate the client token in the cache when the external service responded with 401 error code.
         */
        if ($response->status() === Response::HTTP_UNAUTHORIZED) {
            $response = Http::withToken($this->issueServiceToken(true))->get((string) $url);
        }

        if ($response->clientError() || $response->serverError()) {
            report_logger(['ErrorCode' => 'SL_UR_01'], ['ErrorDetails' => SL_UR_01, 'Exception' => "HTTP request returned status code {$response->status()}."]);

            return static::INV_RESPONSE;
        }

        try {
            return tap(
                ServiceData::create($response->json()),
                fn (ServiceData $data) => $this->cache->put($cacheKey, $data, static::SERVICE_RESPONSE_CACHE_TTL)
            );
        } catch (Throwable $e) {
            report_logger(['ErrorCode' => 'SL_UR_02'], ['ErrorDetails' => SL_UR_02, 'Exception' => $e->getMessage()]);

            return static::INV_RESPONSE;
        }
    }

    protected function issueServiceToken(bool $fresh = false)
    {
        if (!$fresh && $this->cache->has(static::SERVICE_TOKEN_CACHE_KEY)) {
            return $this->cache->get(static::SERVICE_TOKEN_CACHE_KEY);
        }

        $url = static::serviceBaseUrl() . config('services.vs.token_route');

        $res = Http::asForm()->post((string) $url, [
            'client_id' => config('services.vs.client_id'),
            'client_secret' => config('services.vs.client_secret'),
            'grant_type' => 'client_credentials',
            'scope' => '*'
        ]);

        $accessToken = Arr::get($res, 'access_token');
        $expiresIn = Arr::get($res, 'expires_in');

        return tap(
            $accessToken,
            fn () => $this->cache->put(static::SERVICE_TOKEN_CACHE_KEY, $accessToken, $expiresIn)
        );
    }

    protected static function resolveVendorRoute(string $name): string
    {
        $key = 'services.vs.service_routes.' . $name;

        if (!config()->has($key)) {
            ServiceLookupRoute::invalidName();
        }

        return config($key);
    }

    protected static function serviceBaseUrl(): string
    {
        return (string) Str::of(config('services.vs.url'))->finish('/');
    }

    protected static function serviceResponseCacheKey(string $url): string
    {
        return static::SERVICE_RESPONSE_CACHE_KEY . '.' . $url;
    }
}
