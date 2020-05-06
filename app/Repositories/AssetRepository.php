<?php

namespace App\Repositories;

use App\Contracts\Repositories\AssetRepository as Contract;
use App\Repositories\Exceptions\InvalidModel;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class AssetRepository extends SearchableRepository implements Contract
{
    protected Asset $asset;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function chunk(int $count, callable $callback, array $with = [], ?callable $clause = null): bool
    {
        return $this->asset->on(MYSQL_UNBUFFERED)
            ->when($clause, $clause)
            ->with($with)->chunk($count, $callback);
    }

    public function locationsQuery(): Builder
    {
        return $this->asset
            ->query()
            ->join('addresses', fn (JoinClause $join) => $join->on('addresses.id', '=', 'assets.address_id')->whereNotNull('addresses.location_id')->whereNull('addresses.deleted_at'))
            ->join('locations', fn (JoinClause $join) => $join->on('locations.id', '=', 'addresses.location_id')->whereNull('locations.deleted_at'))
            ->with('location')
            ->groupByRaw('locations.id');
    }

    public function countByLocation(string $locationId): int
    {
        return $this->asset->whereHas('location', fn (Builder $q) => $q->whereKey($locationId))->count();
    }

    public function sumByLocation(string $locationId): int
    {
        return $this->asset->whereHas('location', fn (Builder $q) => $q->whereKey($locationId))->sum('unit_price');
    }

    public function getByLocation(string $locationId)
    {
        return $this->asset->whereHas('location', fn (Builder $q) => $q->whereKey($locationId))->get();
    }

    public function count(array $where = []): int
    {
        return $this->asset->query()->where($where)->count();
    }

    public function findOrFail(string $id): Asset
    {
        return $this->asset->whereKey($id)->firstOrFail();
    }

    public function paginate()
    {
        return parent::all();
    }

    public function create(array $attributes): Asset
    {
        return tap($this->asset->make($attributes))->saveOrFail();
    }

    public function update($asset, array $attributes): Asset
    {
        if (is_string($asset)) {
            $asset = $this->findOrFail($asset);
        }

        if (!$asset instanceof Asset) {
            throw InvalidModel::key(Asset::class, __METHOD__, $asset);
        }

        return DB::transaction(
            fn () => tap($asset)->update($attributes)
        );
    }

    public function delete($asset): bool
    {
        if (is_string($asset)) {
            $asset = $this->findOrFail($asset);
        }

        if (!$asset instanceof Asset) {
            throw InvalidModel::key(Asset::class, __METHOD__, $asset);
        }

        return $asset->delete();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByProductNumber::class,
            \App\Http\Query\OrderBySerialNumber::class,
            \App\Http\Query\OrderBySku::class,
            \App\Http\Query\OrderByBaseWarrantyStartDate::class,
            \App\Http\Query\OrderByBaseWarrantyEndDate::class,
            \App\Http\Query\OrderByActiveWarrantyStartDate::class,
            \App\Http\Query\OrderByActiveWarrantyEndDate::class,
            \App\Http\Query\OrderByVendorShortCode::class,
            \App\Http\Query\OrderByAssetCategory::class,
            \App\Http\Query\FilterByLocation::class,
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->asset;
    }

    protected function searchableFields(): array
    {
        return [
            'vendor_short_code',
            'country_code',
            'product_number',
            'serial_number',
            'service_description',
            'product_description',
            'pricing_document',
            'system_handle',
            'service_agreement_id',
            'base_warranty_start_date',
            'base_warranty_end_date',
            'active_warranty_start_date',
            'active_warranty_end_date',
            'quantity',
            'unit_price',
            'buy_price'
        ];
    }

    protected function filterableQuery()
    {
        return $this->asset->query()->with('assetCategory');
    }

    protected function searchableScope($query)
    {
        return $query->with('assetCategory');
    }
}
