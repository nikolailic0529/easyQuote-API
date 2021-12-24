<?php

namespace App\Models;

use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\MappedRow;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class WorldwideQuoteAssetsGroup
 *
 * @property string|null $worldwide_quote_version_id
 * @property string|null $replicated_assets_group_id
 * @property string|null $group_name
 * @property string|null $search_text
 *
 * @property-read WorldwideQuoteVersion|null $worldwideQuoteVersion
 * @property-read \App\Models\WorldwideQuoteAsset[]|\Illuminate\Database\Eloquent\Collection<\App\Models\WorldwideQuoteAsset> $assets
 * @property-read float|null $assets_sum_price
 * @property-read int|null $assets_count
 */
class WorldwideQuoteAssetsGroup extends Model
{
    use Uuid;

    protected $guarded = [];

    public function worldwideQuoteVersion(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuoteVersion::class);
    }

    public function replicatedAssetsGroup(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuoteAssetsGroup::class);
    }

    public function replicatedGroupRows(): BelongsToMany
    {
        return $this->belongsToMany(
            WorldwideQuoteAsset::class,
            'worldwide_quote_assets_group_asset',
            'group_id',
            'asset_id',
            $this->replicatedAssetsGroup()->getForeignKeyName()
        );
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(WorldwideQuoteAsset::class, 'worldwide_quote_assets_group_asset', 'group_id', 'asset_id');
    }
}
