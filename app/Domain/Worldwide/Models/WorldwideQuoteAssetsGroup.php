<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class WorldwideQuoteAssetsGroup.
 *
 * @property string|null                                                                                                                                   $worldwide_quote_version_id
 * @property string|null                                                                                                                                   $replicated_assets_group_id
 * @property string|null                                                                                                                                   $group_name
 * @property string|null                                                                                                                                   $search_text
 * @property WorldwideQuoteVersion|null                                                                                                                    $worldwideQuoteVersion
 * @property \App\Domain\Worldwide\Models\WorldwideQuoteAsset[]|\Illuminate\Database\Eloquent\Collection<\App\Domain\Worldwide\Models\WorldwideQuoteAsset> $assets
 * @property float|null                                                                                                                                    $assets_sum_price
 * @property int|null                                                                                                                                      $assets_count
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
