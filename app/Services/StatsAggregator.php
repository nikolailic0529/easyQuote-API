<?php

namespace App\Services;

use App\Contracts\Repositories\{
    CurrencyRepositoryInterface as Currencies
};
use App\Models\Quote\QuoteTotal;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;

class StatsAggregator
{
    protected const VALUE_PRECISION = 2;

    protected const SUMMARY_CACHE_KEY = 'stats.summary';

    protected const SUMMARY_CACHE_TTL = 15 * 60;

    protected QuoteTotal $total;

    protected Currencies $currencies;

    protected ?float $baseRateCache = null;

    public function __construct(QuoteTotal $total, Currencies $currencies)
    {
        $this->total = $total;
        $this->currencies = $currencies;
    }

    public function summary(?CarbonPeriod $period = null)
    {
        $where = [];

        if ($period) {
            $where = [
                ['quote_created_at', '>=', $period->getStartDate()],
                ['quote_created_at', '<=', $period->getEndDate()],
            ];
        }

        $bindings = array_merge($where, [['notification_time', '=', setting('notification_time')->d]]);

        if ($summary = cache()->tags(static::SUMMARY_CACHE_KEY)->get(static::summaryCacheKey($bindings))) {
            return $summary;
        }

        $totals = $this->total
            ->query()
            ->toBase()
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) THEN `total_price` END) AS `drafted_total`')
            ->selectRaw('SUM(CASE WHEN `quote_submitted_at` IS NOT NULL THEN `total_price` END) AS `submitted_total`')
            ->where($where)
            ->first();

        $expiring_total = $this->total
            ->query()
            ->whereNull('quote_submitted_at')
            ->where('valid_until_date', '>', now())
            ->whereRaw('DATEDIFF(`valid_until_date`, NOW()) <= ?', [setting('notification_time')->d])
            ->sum('total_price');

        $result = [];

        Arr::set($result, 'totals.quotes.drafted', round((float) $totals->drafted_total * $this->baseRate(), static::VALUE_PRECISION));
        Arr::set($result, 'totals.quotes.submitted', round((float) $totals->submitted_total * $this->baseRate(), static::VALUE_PRECISION));
        Arr::set($result, 'totals.quotes.expiring', round((float) $expiring_total * $this->baseRate(), static::VALUE_PRECISION));

        Arr::set($result, 'period.start_date', optional($period, fn ($p) => $p->getStartDate()->toDateString()));
        Arr::set($result, 'period.end_date', optional($period, fn ($p) => $p->getEndDate()->toDateString()));

        Arr::set($result, 'base_currency', setting('base_currency'));

        return tap(
            $result,
            fn ($result) => cache()->tags(static::SUMMARY_CACHE_KEY)->put(
                static::summaryCacheKey($bindings),
                $result,
                static::SUMMARY_CACHE_TTL,
            )
        );
    }

    public function flushSummaryCache(): void
    {
        cache()->tags(static::SUMMARY_CACHE_KEY)->flush();
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

        return $this->baseRateCache = $currency->exchangeRate->exchange_rate;
    }

    protected static function summaryCacheKey(array $bindings = []): string
    {
        return static::SUMMARY_CACHE_KEY . ':' . implode('|', Arr::flatten($bindings));
    }
}
