<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string|null                                                          $replicated_rows_group_id
 * @property mixed                                                                $group_name
 * @property mixed                                                                $search_text
 * @property mixed                                                                $worldwide_distribution_id
 * @property Collection<MappedRow>|\App\Domain\DocumentMapping\Models\MappedRow[] $rows
 * @property mixed                                                                $is_selected
 * @property string|null                                                          $rows_sum
 * @property WorldwideDistribution|null                                           $worldwideDistribution
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
            related: \App\Domain\DocumentMapping\Models\MappedRow::class,
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
            related: \App\Domain\DocumentMapping\Models\MappedRow::class,
            table: 'distribution_rows_group_mapped_row',
            foreignPivotKey: 'rows_group_id'
        ), function (BelongsToMany $relation) {
            $relation
                ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }
}
