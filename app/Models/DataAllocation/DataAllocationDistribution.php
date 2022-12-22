<?php

namespace App\Models\DataAllocation;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $entity_order
 */
class DataAllocationDistribution extends Pivot
{
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(DataAllocationFile::class, foreignKey: 'file_id');
    }
}
