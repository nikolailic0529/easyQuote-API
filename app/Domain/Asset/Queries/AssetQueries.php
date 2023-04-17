<?php

namespace App\Domain\Asset\Queries;

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\AssetCategory;
use App\Domain\Asset\Queries\Scopes\AssetScope;
use App\Domain\Company\Models\Company;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
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
                                protected Gate $gate)
    {
    }

    public function assetUniquenessQuery(string $serialNumber,
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
        $userModel = new User();

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

                $userModel->qualifyColumn('user_fullname'),
            ])
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(), $assetModel->user()->getQualifiedForeignKeyName())
            ->join($assetCategoryModel->getTable(), $assetCategoryModel->getQualifiedKeyName(), $assetModel->assetCategory()->getQualifiedForeignKeyName())
            ->join($vendorModel->getTable(), $vendorModel->getQualifiedKeyName(), $assetModel->vendor()->getQualifiedForeignKeyName());

        return tap($query, static function (Builder $builder) use ($assetModel): void {
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
        $userModel = new User();

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

                $userModel->qualifyColumn('user_fullname'),
            ])
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(), $assetModel->user()->getQualifiedForeignKeyName())
            ->join($assetCategoryModel->getTable(), $assetCategoryModel->getQualifiedKeyName(),
                $assetModel->assetCategory()->getQualifiedForeignKeyName())
            ->leftJoin($rescueQuoteModel->getTable(), static function (JoinClause $join) use ($rescueQuoteModel, $assetModel): void {
                $join->on($rescueQuoteModel->getQualifiedKeyName(), $assetModel->quote()->getQualifiedForeignKeyName());
//                    ->whereNull($rescueQuoteModel->getQualifiedDeletedAtColumn());
            })
            ->leftJoin($rescueCustomerModel->getTable(), $rescueCustomerModel->getQualifiedKeyName(),
                $rescueQuoteModel->customer()->getQualifiedForeignKeyName())
            ->leftJoin($worldwideQuoteModel->getTable(), static function (JoinClause $join) use ($worldwideQuoteModel, $assetModel): void {
                $join->on($worldwideQuoteModel->getQualifiedKeyName(), $assetModel->quote()->getQualifiedForeignKeyName());
//                    ->whereNull($worldwideQuoteModel->getQualifiedDeletedAtColumn());
            })
            ->leftJoin($opportunityModel->getTable(), $opportunityModel->getQualifiedKeyName(),
                $worldwideQuoteModel->opportunity()->getQualifiedForeignKeyName())
            ->leftJoin("{$companyModel->getTable()} as primary_account", "primary_account.{$companyModel->getKeyName()}",
                $opportunityModel->primaryAccount()->getQualifiedForeignKeyName())
            ->tap(AssetScope::from($request, $this->gate));

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
            ->join('addresses', static function (JoinClause $join): void {
                $join->on('addresses.id', '=', 'assets.address_id')
                    ->whereNotNull('addresses.location_id')
                    ->whereNull('addresses.deleted_at');
            })
            ->join('locations', static function (JoinClause $join): void {
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
            ->whereHas('location', static fn (Builder $q) => $q->whereKey($locationId))
            ->toBase();
    }
}
