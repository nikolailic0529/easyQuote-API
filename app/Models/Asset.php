<?php

namespace App\Models;

use App\Contracts\SearchableEntity;
use App\Models\Quote\Quote;
use App\Traits\{BelongsToUser, Uuid,};
use App\Traits\{Auth\Multitenantable, Search\Searchable,};
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property AssetCategory $assetCategory
 * @property Location $location
 */
class Asset extends Model implements SearchableEntity
{
    use Uuid,
        Multitenantable,
        BelongsToUser,
        Searchable,
        HasRelationships,
        EloquentJoin,
        SoftDeletes;

    protected $fillable = [
        'asset_category_id',
        'quote_id',
        'vendor_id',
        'address_id',
        'vendor_short_code',
        'item_number',
        'product_number',
        'serial_number',
        'product_description',
        'service_description',
        'base_warranty_start_date',
        'base_warranty_end_date',
        'active_warranty_start_date',
        'active_warranty_end_date',
        'unit_price',
        'buy_price',
        'product_image',
        'is_migrated',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'buy_price' => 'decimal:2',
        'is_migrated' => 'boolean',
    ];

    protected $dates = [
        'base_warranty_start_date', 'base_warranty_end_date',
        'active_warranty_start_date', 'active_warranty_end_date',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withDefault();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withDefault();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withDefault();
    }

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class)->withDefault();
    }

    public function location(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->address(), (new Address)->location());
    }

    public function country(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->address(), (new Address)->country())->withDefault();
    }

    public function customer(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->quote(), (new Quote)->customer())->withDefault();
    }

    public function toSearchArray(): array
    {
        return [
            'vendor_short_code' => $this->vendor_short_code,
            'category_name' => $this->assetCategory->name,
            'product_number' => $this->product_number,
            'serial_number' => $this->serial_number,
            'sku' => $this->sku,
            'service_description' => $this->service_description,
            'product_description' => $this->product_description,
            'pricing_document' => $this->pricing_document,
            'system_handle' => $this->system_handle,
            'service_agreement_id' => $this->service_agreement_id,
            'base_warranty_start_date' => optional($this->base_warranty_start_date)->format(config('date.format')),
            'base_warranty_end_date' => optional($this->base_warranty_end_date)->format(config('date.format')),
            'active_warranty_start_date' => optional($this->active_warranty_start_date)->format(config('date.format')),
            'active_warranty_end_date' => optional($this->active_warranty_end_date)->format(config('date.format')),
            'unit_price' => $this->unit_price,
            'buy_price' => $this->buy_price,
            'rfq_number' => $this->customer->rfq,
        ];
    }
}
