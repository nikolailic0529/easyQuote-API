<?php

namespace App\Services;

use App\DTO\{CustomersSummary, Summary,};
use App\Enum\OpportunityStatus;
use App\Enum\QuoteStatus;
use App\Models\{Asset,
    AssetTotal,
    Company,
    Customer\CustomerTotal,
    Data\Currency,
    OpportunityTotal,
    Quote\QuoteLocationTotal,
    Quote\QuoteTotal};
use Carbon\CarbonPeriod;
use Grimzy\LaravelMysqlSpatial\{Eloquent\BaseBuilder, Types\Point, Types\Polygon};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class StatsAggregator
{
    protected const MAP_BOUNDS_LIMIT = 100;

    protected ?float $baseRateCache = null;

    public function quotesSummary(?CarbonPeriod $period = null,
                                  ?string $countryId = null,
                                  ?string $currencyId = null,
                                  ?string $userId = null): Summary
    {
        $quoteTotals = QuoteTotal::query()
            ->selectRaw('COUNT(ISNULL(quote_submitted_at) AND quote_status = ? OR NULL) AS drafted_quotes_count', [QuoteStatus::ALIVE])
            ->selectRaw('COUNT(quote_submitted_at IS NOT NULL AND quote_status = ? OR NULL) AS submitted_quotes_count', [QuoteStatus::ALIVE])
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) AND quote_status = ? THEN `total_price` END) AS drafted_quotes_value', [QuoteStatus::ALIVE])
            ->selectRaw('SUM(CASE WHEN quote_submitted_at IS NOT NULL AND quote_status = ? THEN total_price END) AS submitted_quotes_value', [QuoteStatus::ALIVE])
            ->selectRaw('COUNT(quote_status = ? OR NULL) AS dead_quotes_count', [QuoteStatus::DEAD])
            ->selectRaw('SUM(CASE WHEN quote_status = ? THEN total_price END) AS dead_quotes_value', [QuoteStatus::DEAD])
            ->when(!is_null($period), function (Builder $builder) use ($period) {
                $builder->whereBetween('quote_created_at', [$period->getStartDate(), $period->getEndDate()]);
            })
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->whereHas('countries', function (Builder $relation) use ($countryId) {
                    $relation->whereKey($countryId);
                });
            })
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->toBase()
            ->first();

        $expiringQuotesSummary = $this->expiringQuotesSummary($period, $countryId, $userId);

        $customersSummary = $this->customersSummary($countryId, $userId);

        $locationsSummary = $this->locationsSummary($countryId, $userId);

        $assetsSummary = $this->assetsSummary($period, $countryId, $userId);

        $salesOrderSummary = QuoteTotal::query()
            ->join('sales_orders', function (JoinClause $joinClause) {
                $joinClause->on('sales_orders.worldwide_quote_id', 'quote_totals.quote_id');
            })
            ->whereNull('sales_orders.deleted_at')
            ->selectRaw('SUM(CASE WHEN sales_orders.submitted_at IS NULL THEN quote_totals.total_price END) as drafted_sales_orders_value')
            ->selectRaw('SUM(CASE WHEN sales_orders.submitted_at IS NOT NULL THEN quote_totals.total_price END) as submitted_sales_orders_value')
            ->selectRaw('COUNT(sales_orders.submitted_at IS NULL OR NULL) as drafted_sales_orders_count')
            ->selectRaw('COUNT(sales_orders.submitted_at IS NOT NULL OR NULL) as submitted_sales_orders_count')
            ->when(!is_null($period), function (Builder $builder) use ($period) {
                $builder->whereBetween('sales_orders.created_at', [$period->getStartDate(), $period->getEndDate()]);
            })
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->whereHas('countries', function (Builder $relation) use ($countryId) {
                    $relation->whereKey($countryId);
                });
            })
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('sales_orders.user_id', $userId);
            })
            ->toBase()
            ->first();

        $lostOpportunitySummary = OpportunityTotal::query()
            ->selectRaw("COUNT(0) as lost_opportunities_count")
            ->selectRaw('SUM(base_opportunity_amount) as lost_opportunities_value')
            ->where('opportunity_status', OpportunityStatus::LOST)
            ->when(!is_null($period), function (Builder $builder) use ($period) {
                $builder->whereBetween('opportunity_created_at', [$period->getStartDate(), $period->getEndDate()]);
            })
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->whereHas('countries', function (Builder $relation) use ($countryId) {
                    $relation->whereKey($countryId);
                });
            })
            ->toBase()
            ->first();

        $totals = (array)$quoteTotals +
            (array)$expiringQuotesSummary +
            (array)$customersSummary +
            (array)$locationsSummary +
            (array)$assetsSummary +
            (array)$salesOrderSummary +
            (array)$lostOpportunitySummary;

        return Summary::fromArrayOfTotals(
            $totals,
            $this->currencyRate($currencyId),
            setting('base_currency'),
            $period
        );
    }

    public function mapQuoteTotals(Point $center, Polygon $polygon, ?string $userId = null)
    {
        $where = [];

        if ($userId) {
            array_push($where, ['user_id', '=', $userId]);
        }

        $bindings = ['polygon' => (string)$polygon, 'c_lat' => $center->getLat(), 'c_lng' => $center->getLng()];

        return QuoteLocationTotal::query()
            ->select('location_id', 'lat', 'lng', 'location_address', 'total_drafted_count', 'total_submitted_count')
            ->selectRaw('`total_drafted_value` * ? AS `total_drafted_value`', [$this->baseRate()])
            ->selectRaw('`total_submitted_value` * ? AS `total_submitted_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon)
            ->orderByDistance('location_coordinates', $center)
            ->where($where)
            ->groupBy('user_id')
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->get()
            ->append(['total_value', 'total_count']);
    }

    public function customersSummaryList(?CarbonPeriod $period = null, ?string $countryId = null, ?string $currencyId = null, ?string $userId = null): array
    {
        $result = QuoteTotal::query()
            ->selectRaw('SUM(`total_price`) AS `total_value`')
            ->selectRaw('COUNT(*) AS `total_count`')
            ->addSelect('company_id', 'customer_name')
            ->groupBy('customer_name')
            ->orderByDesc('total_value')
            ->where('customer_name', '<>', '')
            ->when(!is_null($period), function (Builder $builder) use ($period) {
                $builder->whereBetween('quote_created_at', [$period->getStartDate(), $period->getEndDate()]);
            })
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->whereHas('countries', function (Builder $builder) use ($countryId) {
                    $builder->whereKey($countryId);
                });
            })
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->take(15)
            ->toBase()
            ->get();

        return CustomersSummary::create($result, $this->currencyRate($currencyId))->toArray();
    }

    public function mapCustomerTotals(Polygon $polygon, ?string $userId = null)
    {
        /** @var Builder $query */
        $query = CustomerTotal::query()
            ->select('company_id', 'customer_name', 'lat', 'lng', 'location_address', 'total_count')
            ->selectRaw('`total_value` * ? AS `total_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon);

        return $query
            ->orderByDesc('total_count')
            ->groupBy('location_id')
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->get();
    }

    public function mapAssetTotals(Point $center, Polygon $polygon, ?string $userId)
    {
        return AssetTotal::query()
            ->select('location_id', 'lat', 'lng', 'location_address', 'total_value', 'total_count')
            ->selectRaw('`total_value` * ? AS `total_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon)
            ->orderByDistance('location_coordinates', $center)
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->withCasts([
                'total_value' => 'float'
            ])
            ->get();
    }

    public function expiringQuotesSummary(?CarbonPeriod $period = null, ?string $countryId = null, ?string $userId = null): object
    {
        return QuoteTotal::query()
            ->selectRaw('COUNT(*) AS `expiring_quotes_count`')
            ->selectRaw('SUM(`total_price`) AS `expiring_quotes_value`')
            ->where('quote_status', QuoteStatus::ALIVE)
            ->when(
                !is_null($period),
                function (Builder $builder) use ($period) {
                    $builder->whereBetween('valid_until_date', [$period->getStartDate(), $period->getEndDate()]);
                },
                function (Builder $builder) {
                    $builder->where('valid_until_date', '>=', today());
                }

            )
            ->when(!is_null($countryId), function (Builder $builder) use ($countryId) {
                $builder->whereHas('countries', function (Builder $relation) use ($countryId) {
                    $relation->whereKey($countryId);
                });
            })
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->toBase()
            ->first();
    }

    public function locationsSummary(?string $countryId = null, ?string $userId = null): object
    {
        $where = [];

        if ($countryId) {
            array_push($where, ['country_id', '=', $countryId]);
        }

        if ($userId) {
            array_push($where, ['user_id', '=', $userId]);
        }

        return CustomerTotal::query()
            ->toBase()
            ->selectRaw('COUNT(*) AS `locations_total`')
            ->distinct('address_id')
            ->where($where)
            ->first();
    }

    public function customersSummary(?string $countryId = null, ?string $userId = null): object
    {
        $where = [];

        if ($countryId) {
            array_push($where, ['default_country_id', '=', $countryId]);
        }

        if ($userId) {
            array_push($where, ['user_id', '=', $userId]);
        }

        $where = array_merge(
            [['type', '=', 'External'], ['category', '=', 'End User']],
            $where
        );

        return (object)['customers_count' => Company::query()->where($where)->count()];
    }

    public function assetsSummary(?CarbonPeriod $period = null, ?string $countryId = null, ?string $userId = null): object
    {
        $result = Asset::query()
            ->when(
                $period,
                fn(Builder $query) => $query->selectRaw('COUNT(`active_warranty_end_date` <= ? AND `active_warranty_end_date` >= ? OR NULL) AS `assets_renewals_count`', [
                    $period->getEndDate()->endOfDay(), $period->getStartDate()->endOfDay()
                ]),
                fn(Builder $query) => $query->selectRaw('COUNT(`active_warranty_end_date` <= NOW() OR NULL) AS `assets_renewals_count`')
            )
            ->when(
                $period,
                fn(Builder $query) => $query->selectRaw('SUM(CASE WHEN `active_warranty_end_date` <= ? AND `active_warranty_end_date` >= ? THEN `unit_price` END) AS `assets_renewals_value`', [
                    $period->getEndDate()->endOfDay(), $period->getStartDate()->endOfDay()
                ]),
                fn(Builder $query) => $query->selectRaw('SUM(CASE WHEN `active_warranty_end_date` <= NOW() THEN `unit_price` END) AS `assets_renewals_value`')
            )
            ->selectRaw('COUNT(*) AS `assets_count`')
            ->selectRaw('SUM(`unit_price`) AS `assets_value`')
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->when($countryId, fn(Builder $q) => $q->whereHas('country', fn(Builder $country) => $country->whereKey($countryId)))
            ->toBase()
            ->first();

        $result->assets_locations_count = AssetTotal::query()
            ->whereNotNull('location_id')
            ->distinct('location_id')
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->toBase()
            ->count();

        return $result;
    }

    /**
     * Retrieve quote totals by specific location.
     *
     * @param string $locationId
     * @param string|null $userId
     * @return mixed
     */
    public function quotesByLocation(string $locationId, ?string $userId = null)
    {
        return QuoteTotal::query()
            ->where('location_id', $locationId)
            ->select('quote_id', 'customer_name', 'rfq_number', 'quote_created_at', 'quote_submitted_at', 'valid_until_date')
            ->selectRaw('`total_price` * ? as total_price', [$this->baseRate()])
            ->when(!is_null($userId), function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->withCasts([
                'quote_created_at' => 'date:'.config('date.format_time'),
                'quote_submitted_at' => 'date:'.config('date.format_time'),
                'valid_until_date' => 'date:'.config('date.format'),
                'total_price' => 'float'
            ])
            ->get();
    }

    protected function baseRate(): float
    {
        if (isset($this->baseRateCache)) {
            return $this->baseRateCache;
        }

        /** @var Currency|null $currency */
        $currency = Currency::query()->where('code', setting('base_currency'))->first();

        if ($currency === null) {
            return $this->baseRateCache = 1;
        }

        return $this->baseRateCache = $currency->exchangeRate->exchange_rate;
    }

    protected function currencyRate(?string $id): float
    {
        if (null === $id) {
            return $this->baseRate();
        }

        $currency = Currency::query()->find($id);

        if (!$currency instanceof Currency) {
            return $this->baseRate();
        }

        return $currency->exchangeRate->exchange_rate;
    }
}
