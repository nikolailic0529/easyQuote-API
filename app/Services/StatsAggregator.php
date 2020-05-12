<?php

namespace App\Services;

use App\Contracts\Repositories\{
    CompanyRepositoryInterface as Companies,
    CurrencyRepositoryInterface as Currencies
};
use App\DTO\{
    CustomersSummary,
    Summary,
};
use App\Models\{
    Asset,
    AssetTotal,
    Quote\QuoteTotal,
    Quote\QuoteLocationTotal,
    Customer\CustomerTotal,
    Data\Currency,
};
use Grimzy\LaravelMysqlSpatial\{
    Types\Point,
    Types\Polygon,
};
use Illuminate\Support\Arr;
use Illuminate\Contracts\Cache\Repository as Cache;
use Carbon\CarbonPeriod;

class StatsAggregator
{
    public const SUMMARY_CACHE_TAG = 'stats.summary';

    protected const QUOTES_SUMMARY_CACHE_KEY = 'quotes.summary';

    protected const EXP_QUOTES_SUMMARY_CACHE_KEY = 'quotes.summary.expiring';

    protected const QUOTES_LOCATION_CACHE_KEY = 'quotes.location';

    protected const CUSTOMERS_SUMMARY_CACHE_KEY = 'customers.summary';

    protected const CUSTOMERS_SUMMARY_LIST_CACHE_KEY = 'customers.summary.list';

    protected const CUSTOMERS_LOCATION_CACHE_KEY = 'customers.location';

    protected const ASSETS_LOCATION_CACHE_KEY = 'assets.location';

    protected const LOCATIONS_SUMMARY_CACHE_KEY = 'locations.summary';

    protected const ASSETS_SUMMARY_CACHE_KEY = 'assets.summary';

    protected const BASE_RATE_CACHE_KEY = 'stats.base_rate';

    protected const SUMMARY_CACHE_TTL = 15 * 60;

    protected const MAP_BOUNDS_LIMIT = 100;

    protected QuoteTotal $quoteTotal;

    protected QuoteLocationTotal $quoteLocationTotal;

    protected CustomerTotal $customerTotal;

    protected AssetTotal $assetTotal;

    protected Asset $asset;

    protected Currencies $currencies;

    protected Companies $companies;

    protected Cache $cache;

    protected ?float $baseRateCache = null;

    public function __construct(
        QuoteTotal $quoteTotal,
        QuoteLocationTotal $quoteLocationTotal,
        CustomerTotal $customerTotal,
        AssetTotal $assetTotal,
        Asset $asset,
        Currencies $currencies,
        Companies $companies,
        Cache $cache
    ) {
        $this->quoteTotal = $quoteTotal;
        $this->quoteLocationTotal = $quoteLocationTotal;
        $this->customerTotal = $customerTotal;
        $this->assetTotal = $assetTotal;
        $this->asset = $asset;
        $this->currencies = $currencies;
        $this->companies = $companies;
        $this->cache = $cache;
    }

    public function quotesSummary(?CarbonPeriod $period = null, ?string $countryId = null)
    {
        $where = [];

        if ($period) {
            array_push(
                $where,
                ['quote_created_at', '>=', $period->getStartDate()],
                ['quote_created_at', '<=', $period->getEndDate()]
            );
        }

        if ($countryId) {
            array_push($where, ['country_id', '=', $countryId]);
        }

        $bindings = array_merge($where, [['notification_time', '=', setting('notification_time')->d]]);
        $cacheKey = static::quotesSummaryCacheKey($bindings);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $quoteTotals = $this->quoteTotal
            ->query()
            ->toBase()
            ->selectRaw('COUNT(ISNULL(`quote_submitted_at`) OR NULL) AS `drafted_quotes_count`')
            ->selectRaw('COUNT(`quote_submitted_at` IS NOT NULL OR NULL) AS `submitted_quotes_count`')
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) THEN `total_price` END) AS `drafted_quotes_value`')
            ->selectRaw('SUM(CASE WHEN `quote_submitted_at` IS NOT NULL THEN `total_price` END) AS `submitted_quotes_value`')
            ->where($where)
            ->first();

        $expiringQuotesSummary = $this->expiringQuotesSummary($countryId);

        $customersSummary = $this->customersSummary($countryId);

        $locationsSummary = $this->locationsSummary($countryId);

        $assetsSummary = $this->assetsSummary($countryId);

        $totals = (array) $quoteTotals + (array) $expiringQuotesSummary + (array) $customersSummary + (array) $locationsSummary + (array) $assetsSummary;

        $result = Summary::create($totals, $this->baseRate(), setting('base_currency'), $period);

        return tap(
            $result,
            fn ($result) => $this->cache->put($cacheKey, $result, static::SUMMARY_CACHE_TTL)
        );
    }

    public function mapQuoteTotals(Point $center, Polygon $polygon)
    {
        $bindings = ['polygon' => (string) $polygon, 'c_lat' => $center->getLat(), 'c_lng' => $center->getLng()];

        $cacheKey = static::quotesLocationCacheKey($bindings);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->quoteLocationTotal->query()
            ->select('location_id', 'lat', 'lng', 'location_address', 'total_drafted_count', 'total_submitted_count')
            ->selectRaw('`total_drafted_value` * ? AS `total_drafted_value`', [$this->baseRate()])
            ->selectRaw('`total_submitted_value` * ? AS `total_submitted_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon)
            ->orderByDistance('location_coordinates', $center)
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->get()
            ->append(['total_value', 'total_count']);

        return tap(
            $result,
            fn ($result) => $this->cache->put($cacheKey, $result, static::SUMMARY_CACHE_TTL)
        );
    }

    public function customersSummaryList(?CarbonPeriod $period = null, ?string $countryId = null)
    {
        $where = [];

        if ($period) {
            array_push(
                $where,
                ['quote_created_at', '>=', $period->getStartDate()],
                ['quote_created_at', '<=', $period->getEndDate()]
            );
        }

        if ($countryId) {
            array_push($where, ['country_id', '=', $countryId]);
        }

        $bindings = $where;
        $cacheKey = static::customersListSummaryCacheKey($bindings);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->quoteTotal
            ->query()
            ->toBase()
            ->selectRaw('SUM(`total_price`) AS `total_value`')
            ->selectRaw('COUNT(*) AS `total_count`')
            ->addSelect('company_id', 'customer_name')
            ->groupBy('customer_name')
            ->orderByDesc('total_value')
            ->where($where)
            ->where('customer_name', '<>', '')
            ->take(15)
            ->get();

        $result = CustomersSummary::create($result, $this->baseRate())->toArray();

        return tap(
            $result,
            fn ($result) => $this->cache->put($cacheKey, $result, static::SUMMARY_CACHE_TTL)
        );
    }

    public function mapCustomerTotals(Polygon $polygon)
    {
        $bindings = ['polygon' => (string) $polygon];

        if ($summary = $this->cache->get(static::customersLocationCacheKey($bindings))) {
            return $summary;
        }

        $result = $this->customerTotal->query()
            ->select('company_id', 'customer_name', 'lat', 'lng', 'location_address', 'total_count')
            ->selectRaw('`total_value` * ? AS `total_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon)
            ->orderByDesc('total_count')
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->get();

        return tap(
            $result,
            fn ($result) => $this->cache->put(
                static::customersLocationCacheKey($bindings),
                $result,
                static::SUMMARY_CACHE_TTL
            )
        );
    }

    public function mapAssetTotals(Point $center, Polygon $polygon)
    {
        $bindings = ['polygon' => (string) $polygon, 'c_lat' => $center->getLat(), 'c_lng' => $center->getLng()];

        if ($summary = $this->cache->get(static::assetsLocationCacheKey($bindings))) {
            return $summary;
        }

        $result = $this->assetTotal->query()
            ->select('location_id', 'lat', 'lng', 'location_address', 'total_value', 'total_count')
            ->selectRaw('`total_value` * ? AS `total_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon)
            ->orderByDistance('location_coordinates', $center)
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->withCasts([
                'total_value' => 'float'
            ])
            ->get();

        return tap(
            $result,
            fn ($result) => $this->cache->put(
                static::assetsLocationCacheKey($bindings),
                $result,
                static::SUMMARY_CACHE_TTL
            )
        );
    }

    public function expiringQuotesSummary(?string $countryId = null): object
    {
        $where = [];

        if ($countryId) {
            array_push($where, ['country_id', '=', $countryId]);
        }

        $bindings = $where;
        $cacheKey = static::expQuotesSummaryCacheKey($bindings);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->quoteTotal
            ->query()
            ->toBase()
            ->selectRaw('COUNT(*) AS `expiring_quotes_count`')
            ->selectRaw('SUM(`total_price`) AS `expiring_quotes_value`')
            ->whereNull('quote_submitted_at')
            ->whereRaw('`valid_until_date` > NOW()')
            ->whereRaw('DATEDIFF(`valid_until_date`, NOW()) <= ?', [setting('notification_time')->d])
            ->where($where)
            ->first();

        return tap($result, fn () => $this->cache->put($cacheKey, $result, static::SUMMARY_CACHE_TTL));
    }

    public function locationsSummary(?string $countryId = null): object
    {
        $where = [];

        if ($countryId) {
            array_push($where, ['country_id', '=', $countryId]);
        }

        $bindings = $where;
        $cacheKey = static::locationsSummaryCacheKey($bindings);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->customerTotal
                ->query()
                ->toBase()
                ->selectRaw('COUNT(*) AS `locations_total`')
                ->distinct('address_id')
                ->first();

        return tap($result, fn () => $this->cache->put($cacheKey, $result, static::SUMMARY_CACHE_TTL));
    }

    public function customersSummary(?string $countryId = null): object
    {
        $where = [];

        if ($countryId) {
            array_push($where, ['country_id', '=', $countryId]);
        }

        $bindings = $where;
        $cacheKey = static::customersSummaryCacheKey($bindings);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = (object) ['customers_count' => $this->companies->count(['type' => 'External', 'category' => 'End User'])];

        return tap($result, fn () => $this->cache->put($cacheKey, $result, static::SUMMARY_CACHE_TTL));
    }

    public function assetsSummary(?string $countryId = null): object
    {
        $where = [];

        if ($countryId) {
            array_push($where, ['country_id', '=', $countryId]);
        }

        $bindings = $where;
        $cacheKey = static::assetsSummaryCacheKey($bindings);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->asset->query()
            ->toBase()
            ->selectRaw('COUNT(`active_warranty_end_date` <= NOW() OR NULL) AS `assets_renewals_count`')
            ->selectRaw('SUM(CASE WHEN `active_warranty_end_date` <= NOW() THEN `unit_price` END) AS `assets_renewals_value`')
            ->selectRaw('COUNT(*) AS `assets_count`')
            ->selectRaw('SUM(`unit_price`) AS `assets_value`')
            ->first();

        $result->assets_locations_count = $this->assetTotal->query()
            ->toBase()
            ->whereNotNull('location_id')
            ->distinct('location_id')
            ->count();

        return tap($result, fn () => $this->cache->put($cacheKey, $result, static::SUMMARY_CACHE_TTL));
    }

    /**
     * Retrieve quote totals by specific location.
     *
     * @param string $locationId
     * @return mixed
     */
    public function quotesByLocation(string $locationId)
    {
        return $this->quoteTotal
            ->query()
            ->whereLocationId($locationId)
            ->select('quote_id', 'customer_name', 'rfq_number', 'quote_created_at', 'quote_submitted_at', 'valid_until_date')
            ->selectRaw('`total_price` * ? as total_price', [$this->baseRate()])
            ->withCasts([
                'quote_created_at' => 'date:' . config('date.format_time'),
                'quote_submitted_at' => 'date:' . config('date.format_time'),
                'valid_until_date' => 'date:' . config('date.format'),
                'total_price' => 'float'
            ])
            ->get();
    }

    public function flushSummaryCache(): void
    {
        $this->cache->flush();
    }

    protected function baseRate(): float
    {
        if (isset($this->baseRateCache)) {
            return $this->baseRateCache;
        }

        $currency = $this->currencies->findByCode(setting('base_currency'));

        if ($currency === null) {
            return $this->baseRateCache = 1;
        }

        return $this->baseRateCache = $this->cache->sear(
            static::baseRateCacheKey($currency),
            fn () => $currency->exchangeRate->exchange_rate
        );
    }

    protected static function quotesSummaryCacheKey(array $bindings = []): string
    {
        return static::QUOTES_SUMMARY_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function expQuotesSummaryCacheKey(array $bindings = []): string
    {
        return static::EXP_QUOTES_SUMMARY_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function locationsSummaryCacheKey(array $bindings = []): string
    {
        return static::LOCATIONS_SUMMARY_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function customersSummaryCacheKey(array $bindings = []): string
    {
        return static::CUSTOMERS_SUMMARY_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function customersListSummaryCacheKey(array $bindings = []): string
    {
        return static::CUSTOMERS_SUMMARY_LIST_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function customersLocationCacheKey(array $bindings = []): string
    {
        return static::CUSTOMERS_LOCATION_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function quotesLocationCacheKey(array $bindings = []): string
    {
        return static::QUOTES_LOCATION_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function assetsLocationCacheKey(array $bindings = []): string
    {
        return static::ASSETS_LOCATION_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function assetsSummaryCacheKey(array $bindings = []): string
    {
        return static::ASSETS_SUMMARY_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }

    protected static function baseRateCacheKey(Currency $currency): string
    {
        return static::BASE_RATE_CACHE_KEY . ':' . $currency->id;
    }
}
