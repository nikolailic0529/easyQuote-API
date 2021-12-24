<?php

namespace App\Queries;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use App\Models\Vendor;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class AssetQueries
{

    public function __construct(protected Elasticsearch $elasticsearch,
                                protected Gate          $gate)
    {

    }

    public function assetUniquenessQuery(string  $serialNumber,
                                         ?string $productNumber = null,
                                         ?string $ignoreModelKey = null,
                                         ?string $ownerKey = null,
                                         ?string $vendorKey = null): Builder
    {
        return Asset::query()
            ->where('serial_number', $serialNumber)
            ->where('product_number', $productNumber)
            ->whereKeyNot($ignoreModelKey)
            ->where('user_id', $ownerKey)
            ->where('vendor_id', $vendorKey);
    }

    public function listOfCompanyAssetsQuery(Company $company): Builder
    {
        $assetModel = new Asset();
        $assetCategoryModel = new AssetCategory();
        $vendorModel = new Vendor();

        $query = $company->assets()
            ->getQuery()
            ->select([
                $assetModel->getQualifiedKeyName(),
                $assetModel->user()->getQualifiedForeignKeyName(),
                $assetModel->qualifyColumn('asset_category_id'),

                "{$assetCategoryModel->qualifyColumn('name')} as asset_category_name",
                "{$vendorModel->qualifyColumn('short_code')} as vendor_short_code",

                $assetModel->qualifyColumn('base_warranty_start_date'),
                $assetModel->qualifyColumn('base_warranty_end_date'),
                $assetModel->qualifyColumn('active_warranty_start_date'),
                $assetModel->qualifyColumn('active_warranty_end_date'),
                $assetModel->qualifyColumn('product_number'),
                $assetModel->qualifyColumn('serial_number'),
                $assetModel->qualifyColumn('product_image'),
                $assetModel->qualifyColumn('created_at'),
            ])
            ->join($assetCategoryModel->getTable(), $assetCategoryModel->getQualifiedKeyName(), $assetModel->assetCategory()->getQualifiedForeignKeyName())
            ->join($vendorModel->getTable(), $vendorModel->getQualifiedKeyName(), $assetModel->vendor()->getQualifiedForeignKeyName());

        return tap($query, function (Builder $builder) use ($assetModel) {
            $builder->orderByDesc($assetModel->getQualifiedCreatedAtColumn());
        });
    }

    public function paginateAssetsQuery(Request $request = null): Builder
    {
        $request ??= new Request();
        $assetModel = new Asset();

        /** @var User|null $user */
        $user = $request->user();

        $assetCategoryModel = new AssetCategory();
        $rescueQuoteModel = new Quote();
        $rescueCustomerModel = new Customer();
        $worldwideQuoteModel = new WorldwideQuote();
        $opportunityModel = new Opportunity();
        $companyModel = new Company();

        $query = $assetModel->newQuery()
            ->select([
                $assetModel->qualifyColumn('id'),
                $assetModel->qualifyColumn('asset_category_id'),
                "{$assetCategoryModel->qualifyColumn('name')} as asset_category_name",
                $assetModel->qualifyColumn('user_id'),
                $assetModel->qualifyColumn('address_id'),
                $assetModel->qualifyColumn('vendor_id'),
                $assetModel->qualifyColumn('quote_id'),
                new Expression("coalesce(primary_account.name, {$rescueCustomerModel->qualifyColumn('name')}) as customer_name"),
                new Expression("coalesce({$rescueCustomerModel->qualifyColumn('rfq')}, {$worldwideQuoteModel->qualifyColumn('quote_number')}) as customer_rfq_number"),
                $assetModel->qualifyColumn('vendor_short_code'),

                $assetModel->qualifyColumn('unit_price'),
                $assetModel->qualifyColumn('base_warranty_start_date'),
                $assetModel->qualifyColumn('base_warranty_end_date'),
                $assetModel->qualifyColumn('active_warranty_start_date'),
                $assetModel->qualifyColumn('active_warranty_end_date'),
                $assetModel->qualifyColumn('product_number'),
                $assetModel->qualifyColumn('serial_number'),
                $assetModel->qualifyColumn('product_description'),
                $assetModel->qualifyColumn('product_image'),
                $assetModel->qualifyColumn('created_at'),
            ])
            ->join($assetCategoryModel->getTable(), $assetCategoryModel->getQualifiedKeyName(), $assetModel->assetCategory()->getQualifiedForeignKeyName())
            ->leftJoin($rescueQuoteModel->getTable(), function (JoinClause $join) use ($rescueQuoteModel, $assetModel) {
                $join->on($rescueQuoteModel->getQualifiedKeyName(), $assetModel->quote()->getQualifiedForeignKeyName());
//                    ->whereNull($rescueQuoteModel->getQualifiedDeletedAtColumn());
            })
            ->leftJoin($rescueCustomerModel->getTable(), $rescueCustomerModel->getQualifiedKeyName(), $rescueQuoteModel->customer()->getQualifiedForeignKeyName())
            ->leftJoin($worldwideQuoteModel->getTable(), function (JoinClause $join) use ($worldwideQuoteModel, $assetModel) {
                $join->on($worldwideQuoteModel->getQualifiedKeyName(), $assetModel->quote()->getQualifiedForeignKeyName());
//                    ->whereNull($worldwideQuoteModel->getQualifiedDeletedAtColumn());
            })
            ->leftJoin($opportunityModel->getTable(), $opportunityModel->getQualifiedKeyName(), $worldwideQuoteModel->opportunity()->getQualifiedForeignKeyName())
            ->leftJoin("{$companyModel->getTable()} as primary_account", "primary_account.{$companyModel->getKeyName()}", $opportunityModel->primaryAccount()->getQualifiedForeignKeyName());

        if (false === is_null($user) && $this->gate->denies('viewAnyOwnerEntities', Asset::class)) {

            $query->where(function (Builder $builder) use ($user) {

                $quoteType = match ($user->team?->business_division_id) {
                    BD_RESCUE => (new Quote())->getMorphClass(),
                    BD_WORLDWIDE => (new WorldwideQuote())->getMorphClass(),
                    default => null,
                };

                $builder
                    ->where(function (Builder $builder) use ($quoteType) {
                        $builder->where($builder->qualifyColumn('quote_type'), $quoteType)
                            ->orWhereNull($builder->qualifyColumn('quote_type'));
                    })
                    ->where(function (Builder $builder) use ($user) {

                        $ledTeamUsersQuery = $user->ledTeamUsers()->getQuery();

                        $builder->where($builder->qualifyColumn('user_id'), $user->getKey())
                            ->orWhereIn($builder->qualifyColumn('user_id'), $ledTeamUsersQuery->select($ledTeamUsersQuery->qualifyColumn('id'))->toBase());

                    });
            });

        }

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
                'product_number',
                'serial_number',
                'sku',
                'base_warranty_start_date',
                'base_warranty_end_date',
                'active_warranty_start_date',
                'active_warranty_end_date',
                'vendor_short_code',
                'asset_category',
                'quote_id',
                'customer_name',
            ])
            ->qualifyOrderFields(
                created_at: $assetModel->getQualifiedCreatedAtColumn(),
                product_number: $assetModel->qualifyColumn('product_number'),
                serial_number: $assetModel->qualifyColumn('serial_number'),
                sku: $assetModel->qualifyColumn('sku'),
                base_warranty_start_date: $assetModel->qualifyColumn('base_warranty_start_date'),
                base_warranty_end_date: $assetModel->qualifyColumn('base_warranty_end_date'),
                active_warranty_start_date: $assetModel->qualifyColumn('active_warranty_start_date'),
                active_warranty_end_date: $assetModel->qualifyColumn('active_warranty_end_date'),
                vendor_short_code: $assetModel->qualifyColumn('vendor_short_code'),
                asset_category: 'asset_category_name',
                quote_id: 'customer_rfq_number',
            )
            ->enforceOrderBy($assetModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function locationsQuery(): Builder
    {
        return Asset::query()
            ->join('addresses', function (JoinClause $join) {
                $join->on('addresses.id', '=', 'assets.address_id')
                    ->whereNotNull('addresses.location_id')
                    ->whereNull('addresses.deleted_at');
            })
            ->join('locations', function (JoinClause $join) {
                $join->on('locations.id', '=', 'addresses.location_id')
                    ->whereNull('locations.deleted_at');
            })
            ->with('location')
            ->groupByRaw('locations.id');
    }

    public function aggregateByUserAndLocationQuery(string $locationId): BaseBuilder
    {
        return Asset::query()
            ->selectRaw('SUM(`unit_price`) AS `total_value`')
            ->selectRaw('COUNT(*) AS `total_count`')
            ->addSelect('user_id')
            ->groupBy('user_id')
            ->whereHas('location', fn(Builder $q) => $q->whereKey($locationId))
            ->toBase();
    }
}
