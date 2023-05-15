<?php

namespace App\Domain\Stats\Services;

use App\Domain\Asset\Models\Asset;
use App\Domain\Company\Models\Company;
use App\Domain\Currency\Models\Currency;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Stats\DataTransferObjects\CustomersSummary;
use App\Domain\Stats\DataTransferObjects\Summary;
use App\Domain\Stats\DataTransferObjects\SummaryRequestData;
use App\Domain\Stats\Models\AssetTotal;
use App\Domain\Stats\Models\CustomerTotal;
use App\Domain\Stats\Models\QuoteLocationTotal;
use App\Domain\Stats\Models\QuoteTotal;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Enum\OpportunityStatus;
use App\Domain\Worldwide\Enum\QuoteStatus;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Carbon\CarbonPeriod;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

class StatsAggregationService
{
    protected const MAP_BOUNDS_LIMIT = 100;

    protected ?float $baseRateCache = null;

    protected array $userCache = [];

    public function __construct(
        protected readonly Gate $gate,
        protected readonly Guard $guard,
    ) {
    }

    public function getQuotesSummary(SummaryRequestData $summaryRequestData): Summary
    {
        $quoteTotalModel = new QuoteTotal();

        $user = $this->resolveUser($summaryRequestData->userId);
        $gate = $this->gate->forUser($user);

        $quoteTotals = $quoteTotalModel->newQuery()
            ->selectRaw('COUNT(ISNULL(quote_submitted_at) AND quote_status = ? OR NULL) AS drafted_quotes_count', [QuoteStatus::ALIVE])
            ->selectRaw('COUNT(quote_submitted_at IS NOT NULL AND quote_status = ? OR NULL) AS submitted_quotes_count', [QuoteStatus::ALIVE])
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) AND quote_status = ? THEN `total_price` END) AS drafted_quotes_value',
                [QuoteStatus::ALIVE])
            ->selectRaw('SUM(CASE WHEN quote_submitted_at IS NOT NULL AND quote_status = ? THEN total_price END) AS submitted_quotes_value',
                [QuoteStatus::ALIVE])
            ->selectRaw('COUNT(quote_status = ? OR NULL) AS dead_quotes_count', [QuoteStatus::DEAD])
            ->selectRaw('SUM(CASE WHEN quote_status = ? THEN total_price END) AS dead_quotes_value', [QuoteStatus::DEAD])
            ->when($summaryRequestData->period instanceof CarbonPeriod, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereBetween('quote_created_at', [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
            })
            ->when(null !== $summaryRequestData->countryId, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereHas('countries', static function (Builder $relation) use ($summaryRequestData): void {
                    $relation->whereKey($summaryRequestData->countryId);
                });
            })
            ->where(static function (Builder $builder) use ($gate, $user, $quoteTotalModel): void {
                $builder->where(static function (Builder $builder) use ($gate, $user, $quoteTotalModel): void {
                    $builder->where($quoteTotalModel->quote()->getMorphType(), (new WorldwideQuote())->getMorphClass());

                    if ($gate->denies('viewAll', WorldwideQuote::class)) {
                        $builder->whereIn(
                            $quoteTotalModel->salesUnit()->getQualifiedForeignKeyName(),
                            $user->salesUnits->merge($user->salesUnitsFromLedTeams)->modelKeys()
                        );
                    }

                    if ($gate->denies('viewCurrentUnitsEntities', WorldwideQuote::class)) {
                        $builder->where($quoteTotalModel->user()->getQualifiedForeignKeyName(), $user->getKey());
                    }
                })
                    ->orWhere(static function (Builder $builder) use ($gate, $user, $quoteTotalModel): void {
                        $builder->where($quoteTotalModel->quote()->getMorphType(), (new Quote())->getMorphClass());

                        if ($gate->denies('viewAll', Quote::class)) {
                            $builder->where($quoteTotalModel->user()->getQualifiedForeignKeyName(), $user->getKey());
                        }
                    });
            })
            ->toBase()
            ->first();

        $expiringQuotesSummary = $this->getExpiringQuotesSummary($summaryRequestData);

        $customersSummary = $this->getCustomersSummary($summaryRequestData);

        $locationsSummary = $this->getLocationsSummary($summaryRequestData);

        $assetsSummary = $this->getAssetsSummary($summaryRequestData);

        $salesOrderSummary = $quoteTotalModel->newQuery()
            ->join('sales_orders', static function (JoinClause $joinClause): void {
                $joinClause->on('sales_orders.worldwide_quote_id', 'quote_totals.quote_id');
            })
            ->whereNull('sales_orders.deleted_at')
            ->selectRaw('SUM(CASE WHEN sales_orders.submitted_at IS NULL THEN quote_totals.total_price END) as drafted_sales_orders_value')
            ->selectRaw('SUM(CASE WHEN sales_orders.submitted_at IS NOT NULL THEN quote_totals.total_price END) as submitted_sales_orders_value')
            ->selectRaw('COUNT(sales_orders.submitted_at IS NULL OR NULL) as drafted_sales_orders_count')
            ->selectRaw('COUNT(sales_orders.submitted_at IS NOT NULL OR NULL) as submitted_sales_orders_count')
            ->when(null !== $summaryRequestData->period, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereBetween('sales_orders.created_at',
                    [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
            })
            ->when(null !== $summaryRequestData->countryId, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereHas('countries', static function (Builder $relation) use ($summaryRequestData): void {
                    $relation->whereKey($summaryRequestData->countryId);
                });
            })
            ->where(static function (Builder $builder) use ($gate, $user, $quoteTotalModel): void {
                if ($gate->denies('viewAll', SalesOrder::class)) {
                    $builder->whereIn(
                        $quoteTotalModel->salesUnit()->getQualifiedForeignKeyName(),
                        $user->salesUnits->merge($user->salesUnitsFromLedTeams)->modelKeys()
                    );
                }

                if ($gate->denies('viewCurrentUnitsEntities', SalesOrder::class)) {
                    $builder->where('sales_orders.user_id', $user->getKey());
                }
            })
            ->toBase()
            ->first();

        $opportunitySummary = $this->getOpportunitySummary($summaryRequestData);

        $totals = (array) $quoteTotals +
            (array) $expiringQuotesSummary +
            (array) $customersSummary +
            (array) $locationsSummary +
            (array) $assetsSummary +
            (array) $salesOrderSummary +
            (array) $opportunitySummary;

        return Summary::fromArrayOfTotals(
            $totals,
            $this->currencyRate($summaryRequestData->currencyId),
            setting('base_currency'),
            $summaryRequestData->period
        );
    }

    public function getOpportunitySummary(SummaryRequestData $summaryRequestData): object
    {
        $oppModel = new Opportunity();

        $user = $this->resolveUser($summaryRequestData->userId);
        $gate = $this->gate->forUser($user);

        $summary = $oppModel->newQuery()
            ->selectRaw('COUNT(status = ? OR NULL) AS lost_opportunities_count', [OpportunityStatus::LOST->value])
            ->selectRaw('COUNT(status = ? OR NULL) AS alive_opportunities_count', [OpportunityStatus::NOT_LOST->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN base_opportunity_amount END) as lost_opportunities_value', [OpportunityStatus::LOST->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN base_opportunity_amount END) as alive_opportunities_value',
                [OpportunityStatus::NOT_LOST->value])
            ->when($summaryRequestData->period instanceof CarbonPeriod, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereBetween('created_at', [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]);
            })
            ->when(null !== $summaryRequestData->countryId, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereHas('countries', static function (Builder $relation) use ($summaryRequestData): void {
                    $relation->whereKey($summaryRequestData->countryId);
                });
            })
            ->where(static function (Builder $builder) use ($gate, $user, $oppModel): void {
                if ($gate->allows('viewAll', Opportunity::class)) {
                    return;
                }

                $builder->whereIn(
                    $oppModel->salesUnit()->getQualifiedForeignKeyName(),
                    $user->salesUnits->merge($user->salesUnitsFromLedTeams)->modelKeys()
                );

                if ($gate->denies('viewCurrentUnitsEntities', Opportunity::class)) {
                    $builder->where(static function (Builder $builder) use ($oppModel, $user): void {
                        $builder->where($oppModel->user()->getQualifiedForeignKeyName(), $user->getKey())
                            ->orWhere($oppModel->accountManager()->getQualifiedForeignKeyName(), $user->getKey());
                    });
                }
            })
            ->toBase()
            ->first();

        $summary->opportunities_count = $summary->alive_opportunities_count;
        $summary->opportunities_value = $summary->alive_opportunities_value;

        return $summary;
    }

    public function getQuoteLocations(Point $center, Polygon $polygon, ?string $userId = null)
    {
        return QuoteLocationTotal::query()
            ->select('location_id', 'lat', 'lng', 'location_address', 'total_drafted_count', 'total_submitted_count')
            ->selectRaw('`total_drafted_value` * ? AS `total_drafted_value`', [$this->baseRate()])
            ->selectRaw('`total_submitted_value` * ? AS `total_submitted_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon)
            ->orderByDistance('location_coordinates', $center)
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->get()
            ->append(['total_value', 'total_count']);
    }

    public function getCustomerTotalLocations(Polygon $polygon, ?string $userId = null): Collection
    {
        /** @var Builder $query */
        $query = CustomerTotal::query()
            ->select('company_id', 'customer_name', 'lat', 'lng', 'location_address', 'total_count')
            ->selectRaw('`total_value` * ? AS `total_value`', [$this->baseRate()])
            ->intersects('location_coordinates', $polygon);

        return $query
            ->orderByDesc('total_count')
            ->groupBy('location_id')
            ->when(null !== $userId, static function (Builder $builder) use ($userId): void {
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
            ->when(null !== $userId, static function (Builder $builder) use ($userId): void {
                $builder->where('user_id', $userId);
            })
            ->limit(static::MAP_BOUNDS_LIMIT)
            ->withCasts([
                'total_value' => 'float',
            ])
            ->get();
    }

    private function resolveUser(string $id): User
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->userCache[$id] ??= User::query()->findOrFail($id);
    }

    public function getExpiringQuotesSummary(SummaryRequestData $summaryRequestData): object
    {
        $quoteTotalModel = new QuoteTotal();

        $user = $this->resolveUser($summaryRequestData->userId);

        $gate = $this->gate->forUser($user);

        return QuoteTotal::query()
            ->selectRaw('COUNT(*) AS `expiring_quotes_count`')
            ->selectRaw('SUM(`total_price`) AS `expiring_quotes_value`')
            ->where('quote_status', QuoteStatus::ALIVE)
            ->where('valid_until_date', '<=', today())
            ->when(null !== $summaryRequestData->countryId, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereHas('countries', static function (Builder $relation) use ($summaryRequestData): void {
                    $relation->whereKey($summaryRequestData->countryId);
                });
            })
            ->where(static function (Builder $builder) use ($gate, $user, $quoteTotalModel): void {
                $builder->where(static function (Builder $builder) use ($gate, $user, $quoteTotalModel): void {
                    $builder->where($quoteTotalModel->quote()->getMorphType(), (new WorldwideQuote())->getMorphClass());

                    if ($gate->denies('viewAll', WorldwideQuote::class)) {
                        $builder->whereIn(
                            $quoteTotalModel->salesUnit()->getQualifiedForeignKeyName(),
                            $user->salesUnits->merge($user->salesUnitsFromLedTeams)->modelKeys()
                        );
                    }

                    if ($gate->denies('viewCurrentUnitsEntities', WorldwideQuote::class)) {
                        $builder->where($quoteTotalModel->user()->getQualifiedForeignKeyName(), $user->getKey());
                    }
                })
                    ->orWhere(static function (Builder $builder) use ($gate, $user, $quoteTotalModel): void {
                        $builder->where($quoteTotalModel->quote()->getMorphType(), (new Quote())->getMorphClass());

                        if ($gate->denies('viewAll', Quote::class)) {
                            $builder->where($quoteTotalModel->user()->getQualifiedForeignKeyName(), $user->getKey());
                        }
                    });
            })
            ->toBase()
            ->first();
    }

    public function getLocationsSummary(SummaryRequestData $summaryRequestData): object
    {
        $totalModel = new CustomerTotal();
        $user = $this->resolveUser($summaryRequestData->userId);
        $gate = $this->gate->forUser($user);

        return $totalModel->newQuery()
            ->selectRaw('COUNT(*) AS `locations_total`')
            ->distinct('address_id')
            ->when(null !== $summaryRequestData->countryId, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->where('country_id', $summaryRequestData->countryId);
            })
            ->where(static function (Builder $builder) use ($gate, $user, $totalModel): void {
                if ($gate->denies('viewAll', Company::class)) {
                    $builder->whereIn(
                        $totalModel->salesUnit()->getQualifiedForeignKeyName(),
                        $user->salesUnits->merge($user->salesUnitsFromLedTeams)->modelKeys()
                    );
                }

                if ($gate->denies('viewCurrentUnitsEntities', Company::class)) {
                    $builder->where($totalModel->user()->getQualifiedForeignKeyName(), $user->getKey());
                }
            })
            ->toBase()
            ->first();
    }

    public function getCustomersSummary(SummaryRequestData $summaryRequestData): object
    {
        $companyModel = new Company();
        $user = $this->resolveUser($summaryRequestData->userId);
        $gate = $this->gate->forUser($user);

        $count = $companyModel->newQuery()
            ->when(null !== $summaryRequestData->countryId, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->where('default_country_id', $summaryRequestData->countryId);
            })
            ->where(static function (Builder $builder) use ($gate, $user, $companyModel): void {
                if ($gate->denies('viewAll', Company::class)) {
                    $builder->whereIn(
                        $companyModel->salesUnit()->getQualifiedForeignKeyName(),
                        $user->salesUnits->merge($user->salesUnitsFromLedTeams)->modelKeys()
                    );
                }

                if ($gate->denies('viewCurrentUnitsEntities', Company::class)) {
                    $builder->where($companyModel->user()->getQualifiedForeignKeyName(), $user->getKey());
                }
            })
            ->count();

        return (object) ['customers_count' => $count];
    }

    public function getCustomersSummaryList(SummaryRequestData $summaryRequestData): array
    {
        $totalModel = new QuoteTotal();
        $companyModel = new Company();

        $user = $this->resolveUser($summaryRequestData->userId);
        $gate = $this->gate->forUser($user);

        $result = $totalModel->newQuery()
            ->selectRaw("SUM({$totalModel->qualifyColumn('total_price')}) AS `total_value`")
            ->selectRaw('COUNT(*) AS `total_count`')
            ->addSelect([
                $totalModel->company()->getQualifiedForeignKeyName(),
                $totalModel->qualifyColumn('customer_name'),
            ])
            ->groupBy($totalModel->qualifyColumn('company_id'))
            ->orderByDesc('total_value')
            ->where($totalModel->qualifyColumn('customer_name'), '<>', '')
            ->join($companyModel->getTable(), $companyModel->getQualifiedKeyName(), $totalModel->company()->getQualifiedForeignKeyName())
            ->when($summaryRequestData->period instanceof CarbonPeriod,
                static function (Builder $builder) use ($totalModel, $summaryRequestData): void {
                    $builder->whereBetween(
                        $totalModel->qualifyColumn('quote_created_at'),
                        [$summaryRequestData->period->getStartDate(), $summaryRequestData->period->getEndDate()]
                    );
                })
            ->when(null !== $summaryRequestData->countryId, static function (Builder $builder) use ($summaryRequestData): void {
                $builder->whereHas('countries', static function (Builder $builder) use ($summaryRequestData): void {
                    $builder->whereKey($summaryRequestData->countryId);
                });
            })
            ->where(static function (Builder $builder) use ($gate, $user, $companyModel): void {
                if ($gate->denies('viewAll', Company::class)) {
                    $builder->whereIn(
                        $companyModel->salesUnit()->getQualifiedForeignKeyName(),
                        $user->salesUnits->merge($user->salesUnitsFromLedTeams)->modelKeys()
                    );
                }

                if ($gate->denies('viewCurrentUnitsEntities', Company::class)) {
                    $builder->where($companyModel->user()->getQualifiedForeignKeyName(), $user->getKey());
                }
            })
            ->take(15)
            ->toBase()
            ->get();

        return CustomersSummary::create($result, $this->currencyRate($summaryRequestData->currencyId))->toArray();
    }

    public function getAssetsSummary(SummaryRequestData $summaryRequestData): object
    {
        $result = Asset::query()
            ->when(
                $summaryRequestData->period instanceof CarbonPeriod,
                static function (Builder $query) use ($summaryRequestData): void {
                    $query->selectRaw('COUNT(`active_warranty_end_date` <= ? AND `active_warranty_end_date` >= ? OR NULL) AS `assets_renewals_count`',
                        [
                            $summaryRequestData->period->getEndDate()->endOfDay(), $summaryRequestData->period->getStartDate()->endOfDay(),
                        ])
                        ->selectRaw('SUM(CASE WHEN `active_warranty_end_date` <= ? AND `active_warranty_end_date` >= ? THEN `unit_price` END) AS `assets_renewals_value`',
                            [
                                $summaryRequestData->period->getEndDate()->endOfDay(), $summaryRequestData->period->getStartDate()->endOfDay(),
                            ]);
                },
                static function (Builder $query): void {
                    $query->selectRaw('COUNT(`active_warranty_end_date` <= NOW() OR NULL) AS `assets_renewals_count`')
                        ->selectRaw('SUM(CASE WHEN `active_warranty_end_date` <= NOW() THEN `unit_price` END) AS `assets_renewals_value`');
                }
            )
            ->selectRaw('COUNT(*) AS `assets_count`')
            ->selectRaw('SUM(`unit_price`) AS `assets_value`')
            ->when(null !== $summaryRequestData->countryId, static function (Builder $q) use ($summaryRequestData): void {
                $q->whereHas('country', static function (Builder $relation) use ($summaryRequestData): void {
                    $relation->whereKey($summaryRequestData->countryId);
                });
            })
            ->toBase()
            ->first();

        $result->assets_locations_count = AssetTotal::query()
            ->whereNotNull('location_id')
            ->distinct('location_id')
            ->toBase()
            ->count();

        return $result;
    }

    public function getQuoteTotalLocations(string $locationId, ?string $userId = null): Collection
    {
        return QuoteTotal::query()
            ->where('location_id', $locationId)
            ->select('quote_id', 'customer_name', 'rfq_number', 'quote_created_at', 'quote_submitted_at', 'valid_until_date')
            ->selectRaw('`total_price` * ? as total_price', [$this->baseRate()])
            ->when(!is_null($userId), static function (Builder $builder) use ($userId): void {
                $builder->where('user_id', $userId);
            })
            ->withCasts([
                'quote_created_at' => 'date:'.config('date.format_time'),
                'quote_submitted_at' => 'date:'.config('date.format_time'),
                'valid_until_date' => 'date:'.config('date.format'),
                'total_price' => 'float',
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
