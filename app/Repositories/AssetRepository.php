<?php

namespace App\Repositories;

use App\Contracts\Repositories\AssetRepository as Contract;
use App\Models\Asset;
use App\Repositories\Exceptions\InvalidModel;
use Illuminate\Database\{Eloquent\Builder, Eloquent\Model,};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;

class AssetRepository extends SearchableRepository implements Contract
{
    protected Asset $asset;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function userQuery(): Builder
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        return $this->asset->query()
            ->unless($user->hasRole(R_SUPER), fn(Builder $q) => $q->whereUserId(auth()->id()));
    }

    public function checkUniqueness(array $where): bool
    {
        return $this->asset->query()->where($where)->doesntExist();
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
            fn() => tap($asset)->update($attributes)
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
            \App\Http\Query\OrderByQuoteId::class,
            \App\Http\Query\FilterByLocation::class,
            \App\Http\Query\DefaultOrderBy::class,
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->asset;
    }

    protected function searchableQuery()
    {
        return $this->userQuery()->with(static::listingRelationships());
    }

    protected function filterableQuery()
    {
        return $this->userQuery()->with(static::listingRelationships());
    }

    protected function searchableScope($query)
    {
        return $query->with(static::listingRelationships());
    }

    protected function searchableFields(): array
    {
        return [
            'category_name',
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
            'buy_price',
            'rfq_number',
        ];
    }

    protected static function listingRelationships(): array
    {
        return [
            'assetCategory' => fn(BelongsTo $q) => $q->cacheForever(),
            'customer' => fn(HasOneDeep $q) => $q->select('rfq')->cacheForever()
        ];
    }
}
