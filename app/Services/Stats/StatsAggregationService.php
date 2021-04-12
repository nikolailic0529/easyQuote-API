<?php

namespace App\Services\Stats;

use App\DTO\{CustomersSummary, Stats\SummaryRequestData, Summary};
use App\Enum\AccountCategory;
use App\Enum\OpportunityStatus;
use App\Enum\QuoteStatus;
use App\Models\{Asset,
    AssetTotal,
    Company,
    Customer\CustomerTotal,
    Data\Currency,
    OpportunityTotal,
    Quote\QuoteLocationTotal,
    Quote\QuoteTotal,
    User
};
use Grimzy\LaravelMysqlSpatial\{Types\Point, Types\Polygon};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class StatsAggregationService
{
    protected const MAP_BOUNDS_LIMIT = 100;

    protected ?float $baseRateCache = null;

    public function getQuotesSummary(SummaryRequestData $summaryRequestData): Summary
    {
        $quoteTotals = QuoteTotal::query()
            ->selectRaw('COUNT(ISNULL(quote_submitted_at) AND quote_status = ? OR NULL) AS drafted_quotes_count', [QuoteStatus::ALIVE])
            ->selectRaw('COUNT(quote_submitted_at IS NOT NULL AND quote_status = ? OR NULL) AS submitted_quotes_count', [QuoteStatus::ALIVE])
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) AND quote_status = ? THEN `total_price` END) AS drafted_quotes_value', [QuoteStatus::ALIVE])
            ->selectRaw('SUM(CASE WHEN quote_submitted_at IS NOT NULL AND quote_status = ? THEN total_price END) AS submitted_quotes_value', [QuoteStatus::ALIVE])
            ->selectRaw('COUNT(quote_status = ? OR NULL) AS dead_quotes_count', [QuoteStatus::DEAD])
            ->selectRaw('SUM(CASE WHEN quote_status = ? THEN total_price END) AS dead_quotes_value', [QuoteStatus::DEAD])
            ->when(!is_null($summaryRequestData->period), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereBetween('quote_created_at', [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
            })
            ->when(!is_null($summaryRequestData->country_id), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereHas('countries', function (Builder $relation) use ($summaryRequestData) {
                    $relation->whereKey($summaryRequestData->country_id);
                });
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {
                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));
                });
            })
            ->whereIn('quote_type', $summaryRequestData->entity_types)
            ->toBase()
            ->first();

        $expiringQuotesSummary = $this->getExpiringQuotesSummary($summaryRequestData);

        $customersSummary = $this->getCustomersSummary($summaryRequestData);

        $locationsSummary = $this->getLocationsSummary($summaryRequestData);

        $assetsSummary = $this->getAssetsSummary($summaryRequestData);

        $salesOrderSummary = QuoteTotal::query()
            ->join('sales_orders', function (JoinClause $joinClause) {
                $joinClause->on('sales_orders.worldwide_quote_id', 'quote_totals.quote_id');
            })
            ->whereNull('sales_orders.deleted_at')
            ->selectRaw('SUM(CASE WHEN sales_orders.submitted_at IS NULL THEN quote_totals.total_price END) as drafted_sales_orders_value')
            ->selectRaw('SUM(CASE WHEN sales_orders.submitted_at IS NOT NULL THEN quote_totals.total_price END) as submitted_sales_orders_value')
            ->selectRaw('COUNT(sales_orders.submitted_at IS NULL OR NULL) as drafted_sales_orders_count')
            ->selectRaw('COUNT(sales_orders.submitted_at IS NOT NULL OR NULL) as submitted_sales_orders_count')
            ->when(!is_null($summaryRequestData->period), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereBetween('sales_orders.created_at', [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
            })
            ->when(!is_null($summaryRequestData->country_id), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereHas('countries', function (Builder $relation) use ($summaryRequestData) {
                    $relation->whereKey($summaryRequestData->country_id);
                });
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {
                    $builder->where($builder->qualifyColumn('sales_orders.user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('sales_orders.user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));
                });
            })
            ->whereIn('quote_type', $summaryRequestData->entity_types)
            ->toBase()
            ->first();

        $lostOpportunitySummary = OpportunityTotal::query()
            ->selectRaw("COUNT(0) as lost_opportunities_count")
            ->selectRaw('SUM(base_opportunity_amount) as lost_opportunities_value')
            ->where('opportunity_status', OpportunityStatus::LOST)
            ->when(!is_null($summaryRequestData->period), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereBetween('opportunity_created_at', [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
            })
            ->when(!is_null($summaryRequestData->country_id), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereHas('countries', function (Builder $relation) use ($summaryRequestData) {
                    $relation->whereKey($summaryRequestData->country_id);
                });
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {
                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhere($builder->qualifyColumn('account_manager_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));
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
            $this->currencyRate($summaryRequestData->currency_id),
            setting('base_currency'),
            $summaryRequestData->period
        );
    }

    public function getQuoteLocations(Point $center, Polygon $polygon, ?string $userId = null)
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

    public function getCustomersSummaryList(SummaryRequestData $summaryRequestData): array
    {
        $result = QuoteTotal::query()
            ->selectRaw('SUM(`total_price`) AS `total_value`')
            ->selectRaw('COUNT(*) AS `total_count`')
            ->addSelect('company_id', 'customer_name')
            ->groupBy('customer_name')
            ->orderByDesc('total_value')
            ->where('customer_name', '<>', '')
            ->when(!is_null($summaryRequestData->period), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereBetween('quote_created_at', [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
            })
            ->when(!is_null($summaryRequestData->country_id), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereHas('countries', function (Builder $builder) use ($summaryRequestData) {
                    $builder->whereKey($summaryRequestData->country_id);
                });
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {
                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));
                });
            })
            ->take(15)
            ->toBase()
            ->get();

        return CustomersSummary::create($result, $this->currencyRate($summaryRequestData->currency_id))->toArray();
    }

    public function getCustomerTotalLocations(Polygon $polygon, ?string $userId = null)
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

    public function getAssetTotalLocations(Point $center, Polygon $polygon, ?string $userId)
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

    public function getExpiringQuotesSummary(SummaryRequestData $summaryRequestData): object
    {
        return QuoteTotal::query()
            ->selectRaw('COUNT(*) AS `expiring_quotes_count`')
            ->selectRaw('SUM(`total_price`) AS `expiring_quotes_value`')
            ->where('quote_status', QuoteStatus::ALIVE)
            ->when(
                !is_null($summaryRequestData->period),
                function (Builder $builder) use ($summaryRequestData) {
                    $builder->whereBetween('valid_until_date', [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
                },
                function (Builder $builder) {
                    $builder->where('valid_until_date', '>=', today());
                }

            )
            ->when(!is_null($summaryRequestData->country_id), function (Builder $builder) use ($summaryRequestData) {
                $builder->whereHas('countries', function (Builder $relation) use ($summaryRequestData) {
                    $relation->whereKey($summaryRequestData->country_id);
                });
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {

                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));

                });
            })
            ->toBase()
            ->first();
    }

    public function getLocationsSummary(SummaryRequestData $summaryRequestData): object
    {
        return CustomerTotal::query()
            ->selectRaw('COUNT(*) AS `locations_total`')
            ->distinct('address_id')
            ->when(!is_null($summaryRequestData->country_id), function (Builder $builder) use ($summaryRequestData) {
                $builder->where('country_id', $summaryRequestData->country_id);
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {

                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));

                });
            })
            ->toBase()
            ->first();
    }

    public function getCustomersSummary(SummaryRequestData $summaryRequestData): object
    {
        $count = Company::query()
            ->where('type', 'External')
            ->where('category', AccountCategory::END_USER)
            ->when(!is_null($summaryRequestData->country_id), function (Builder $builder) use ($summaryRequestData) {
                $builder->where('default_country_id', $summaryRequestData->country_id);
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {

                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));

                });
            })
            ->count();

        return (object)['customers_count' => $count];
    }

    public function getAssetsSummary(SummaryRequestData $summaryRequestData): object
    {
        $result = Asset::query()
            ->when(
                !is_null($summaryRequestData->period),
                function (Builder $query) use ($summaryRequestData) {
                    $query->selectRaw('COUNT(`active_warranty_end_date` <= ? AND `active_warranty_end_date` >= ? OR NULL) AS `assets_renewals_count`', [
                        $summaryRequestData->period->getEndDate()->endOfDay(), $summaryRequestData->period->getStartDate()->endOfDay()
                    ])
                        ->selectRaw('SUM(CASE WHEN `active_warranty_end_date` <= ? AND `active_warranty_end_date` >= ? THEN `unit_price` END) AS `assets_renewals_value`', [
                            $summaryRequestData->period->getEndDate()->endOfDay(), $summaryRequestData->period->getStartDate()->endOfDay()
                        ]);
                },
                function (Builder $query) {
                    $query->selectRaw('COUNT(`active_warranty_end_date` <= NOW() OR NULL) AS `assets_renewals_count`')
                        ->selectRaw('SUM(CASE WHEN `active_warranty_end_date` <= NOW() THEN `unit_price` END) AS `assets_renewals_value`');
                }
            )
            ->selectRaw('COUNT(*) AS `assets_count`')
            ->selectRaw('SUM(`unit_price`) AS `assets_value`')
            ->when(!is_null($summaryRequestData->country_id), function (Builder $q) use ($summaryRequestData) {
                $q->whereHas('country', function (Builder $relation) use ($summaryRequestData) {
                    $relation->whereKey($summaryRequestData->country_id);
                });
            })
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {

                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));

                });
            })
            ->toBase()
            ->first();

        $result->assets_locations_count = AssetTotal::query()
            ->whereNotNull('location_id')
            ->distinct('location_id')
            ->when(false === $summaryRequestData->any_owner_entities, function (Builder $builder) use ($summaryRequestData) {
                $builder->where(function (Builder $builder) use ($summaryRequestData) {

                    $builder->where($builder->qualifyColumn('user_id'), $summaryRequestData->acting_user_id)
                        ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->whereIn('team_id', $summaryRequestData->acting_user_led_teams));

                });
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
    public function getQuoteTotalLocations(string $locationId, ?string $userId = null)
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
