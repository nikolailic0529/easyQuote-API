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
 * @property Collection<MappedRow> $rows
 * @property mixed $is_selected
 * @property string|null $rows_sum
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
        return $this->belongsToMany(
            MappedRow::class,
            'distribution_rows_group_mapped_row',
            'rows_group_id',
            null,
            $this->replicatedRowsGroup()->getForeignKeyName()
        );
    }

    public function rows(): BelongsToMany
    {
        return $this->belongsToMany(MappedRow::class, 'distribution_rows_group_mapped_row', 'rows_group_id');
    }
}
