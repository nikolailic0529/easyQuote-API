<?php

namespace App\Models\QuoteFile;

use App\Models\Quote\WorldwideDistribution;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property string|null $replicated_rows_group_id
 * @property mixed $group_name
 * @property mixed $search_text
 * @property mixed $worldwide_distribution_id
 * @property Collection<MappedRow>|MappedRow[] $rows
 * @property mixed $is_selected
 *
 * @property string|null $rows_sum
 * @property-read WorldwideDistribution|null $worldwideDistribution
 */
class DistributionRowsGroup extends Model
{
    use Uuid;

    protected $guarded = [];

    public function worldwideDistribution(): BelongsTo
    {
        return $this->belongsTo(WorldwideDistribution::class);
    }

    public function replicatedRowsGroup(): BelongsTo
    {
        return $this->belongsTo(DistributionRowsGroup::class);
    }

    public function replicatedGroupRows(): BelongsToMany
    {
        return tap($this->belongsToMany(
            related: MappedRow::class,
            table: 'distribution_rows_group_mapped_row',
            foreignPivotKey: 'rows_group_id',
            parentKey: $this->replicatedRowsGroup()->getForeignKeyName()
        ), function (BelongsToMany $relation) {
           $relation
               ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }

    public function rows(): BelongsToMany
    {
        return tap($this->belongsToMany(
            related: MappedRow::class,
            table: 'distribution_rows_group_mapped_row',
            foreignPivotKey: 'rows_group_id'
        ), function (BelongsToMany $relation) {
          $relation
              ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }
}
